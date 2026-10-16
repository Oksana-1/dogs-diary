"""HTTP security smoke for the explicitly named disposable runtime containers.

Never accepts production URLs. See docs/STATELESS_RUNTIME.md for setup.
"""

import base64
from concurrent.futures import ThreadPoolExecutor
import hashlib
import hmac
from html import unescape
from http.client import HTTPConnection
from http.cookies import SimpleCookie
import json
import re
import secrets
import subprocess
import time
from urllib.parse import urlencode, urlsplit


DATABASE = "dogs_diary_runtime_test"
SECRET = "runtime-isolated-shared-session-secret-2026"
PASSWORD = "Runtime synthetic password 2026!"


def sql(statement):
    return subprocess.check_output([
        "docker", "exec", "dogs-diary-runtime-db", "psql", "-U", "app",
        "-d", DATABASE, "-v", "ON_ERROR_STOP=1", "-Atc", statement,
    ], text=True).strip()


def restart(name):
    subprocess.run(["docker", "restart", name], check=True, stdout=subprocess.DEVNULL)
    port = 18181 if name.endswith("-a") else 18182
    for _ in range(50):
        try:
            if Client().request(port, "/healthz")[0] == 204:
                return
        except (ConnectionError, OSError):
            pass
        time.sleep(0.1)
    raise AssertionError("Disposable app did not restart")


class Client:
    def __init__(self):
        self.cookies = SimpleCookie()

    def request(self, port, path, data=None):
        assert port in (18181, 18182)
        connection = HTTPConnection("127.0.0.1", port, timeout=15)
        headers = {"Host": "diary.test", "X-Forwarded-Proto": "https"}
        # Emulate one HTTPS browser moving between trusted local edge backends.
        headers["Cookie"] = "; ".join(f"{key}={value.value}" for key, value in self.cookies.items())
        if data is not None:
            headers["Content-Type"] = "application/x-www-form-urlencoded"
        connection.request("GET" if data is None else "POST", path,
                           None if data is None else urlencode(data), headers)
        response = connection.getresponse()
        body = response.read().decode()
        for key, value in response.getheaders():
            if key.lower() == "set-cookie":
                cookie = SimpleCookie(value)
                for name, item in cookie.items():
                    if item["max-age"] == "0" or item.value == "deleted":
                        self.cookies.pop(name, None)
                    else:
                        self.cookies[name] = item.value
                if "PHPSESSID=" in value and "deleted" not in value:
                    assert "secure" in value.lower() and "httponly" in value.lower()
                    assert "samesite=lax" in value.lower()
        result = (response.status, response.getheader("Location"), body)
        connection.close()
        return result

    def csrf(self, port, path):
        status, _, body = self.request(port, path)
        assert status == 200, (path, status)
        match = re.search(r'name="_csrf_token" value="([^"]+)"', body)
        assert match, path
        return unescape(match[1])


def main():
    assert sql("SELECT current_database()") == DATABASE
    for name in ("dogs-diary-runtime-a", "dogs-diary-runtime-b"):
        config = json.loads(subprocess.check_output(["docker", "inspect", name], text=True))[0]
        env = dict(item.split("=", 1) for item in config["Config"]["Env"])
        assert f"/{DATABASE}?" in env["DATABASE_URL"]
        assert env["APP_SECRET"] == SECRET
        assert not config["Mounts"], "Smoke instances must not share filesystem volumes"

    # Reset only disposable test budgets, before the scenario under test starts.
    sql("DELETE FROM security_rate_limits")

    email = f"smoke-{secrets.token_hex(6)}@dogs-diary.test"
    client = Client()
    token = client.csrf(18181, "/sign-up")
    status, _, _ = client.request(18182, "/sign-up", {
        "_csrf_token": token, "name": "Synthetic Smoke", "email": email,
        "password": PASSWORD, "password_confirmation": PASSWORD, "terms": "1",
    })
    assert status == 302
    assert client.request(18181, "/api/dogs")[0] == 200
    restart("dogs-diary-runtime-a")
    assert client.request(18181, "/api/dogs")[0] == 200
    token = client.csrf(18181, "/")
    assert client.request(18182, "/logout", {"_csrf_token": "invalid"})[0] == 403
    assert client.request(18181, "/api/dogs")[0] == 200
    assert client.request(18182, "/logout", {"_csrf_token": token})[0] == 302
    assert client.request(18181, "/api/dogs")[0] == 401

    token = client.csrf(18181, "/login")
    restart("dogs-diary-runtime-b")
    assert client.request(18182, "/login", {
        "_csrf_token": token, "email": email, "password": PASSWORD,
    })[0] == 302
    assert client.request(18181, "/api/dogs")[0] == 200

    token = client.csrf(18181, "/reset-password")
    assert client.request(18182, "/reset-password", {"_csrf_token": token, "email": email})[0] == 302
    user_id = int(sql(f"SELECT id FROM app_user WHERE email = '{email}'"))
    assert int(sql(f"SELECT count(*) FROM reset_password_request WHERE user_id = {user_id}")) == 1
    # Install a known disposable token fixture; Preview intentionally has no mail delivery.
    selector, verifier = secrets.token_hex(10), secrets.token_hex(10)
    expiry = int(time.time()) + 3600
    payload = json.dumps([verifier, str(user_id), expiry], separators=(",", ":"))
    digest = base64.b64encode(hmac.new(SECRET.encode(), payload.encode(), hashlib.sha256).digest()).decode()
    sql(f"UPDATE reset_password_request SET selector = '{selector}', hashed_token = '{digest}', "
        f"expires_at = to_timestamp({expiry}) AT TIME ZONE 'UTC' WHERE user_id = {user_id}")
    reset_path = "/reset-password/" + selector + verifier
    token = client.csrf(18181, reset_path)
    restart("dogs-diary-runtime-b")
    new_password = PASSWORD + " changed"
    assert client.request(18182, reset_path, {
        "_csrf_token": token, "password": new_password, "password_confirmation": new_password,
    })[0] == 302
    assert client.request(18181, reset_path)[0] == 422
    assert client.request(18181, "/api/dogs")[0] == 401

    token = client.csrf(18181, "/login")
    assert client.request(18182, "/login", {
        "_csrf_token": token, "email": email, "password": new_password,
    })[0] == 302
    assert client.request(18181, "/api/dogs")[0] == 200
    session_id = client.cookies["PHPSESSID"].value
    assert re.fullmatch(r"[A-Za-z0-9,-]+", session_id)
    sql(f"UPDATE sessions SET sess_lifetime = 0 WHERE sess_id = '{session_id}'")
    assert client.request(18181, "/api/dogs")[0] == 401

    # Five failures in separate browser sessions, split across concurrent instances.
    def fail_login(attempt):
        browser = Client()
        port = 18181 + attempt % 2
        token = browser.csrf(port, "/login")
        response = browser.request(port, "/login", {
            "_csrf_token": token, "email": email, "password": "wrong",
        })
        assert response[0] == 302 and urlsplit(response[1]).path == "/login", response[:2]

    with ThreadPoolExecutor(max_workers=5) as executor:
        list(executor.map(fail_login, range(5)))
    restart("dogs-diary-runtime-a")
    browser = Client()
    token = browser.csrf(18182, "/login")
    response = browser.request(18181, "/login", {
        "_csrf_token": token, "email": email, "password": new_password,
    })
    assert response[0] == 302 and urlsplit(response[1]).path == "/login", "Sixth login must be throttled even with a correct password"
    assert browser.request(18182, "/api/dogs")[0] == 401
    print("PASS: cross-instance CSRF/login/logout/reset, restart persistence, expiry and concurrent login throttling")


if __name__ == "__main__":
    main()
