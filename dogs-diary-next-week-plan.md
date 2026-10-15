# Dogs Diary — production deployment plan

> Updated 15 October 2026: the next objective is production on Vercel.
> R2 media storage is the immediate implementation task. Earlier schedules below
> are historical; they do not authorize repeated exports or new databases.

## Current verified state

- A protected Vercel Preview was deployed successfully:
  https://dogs-diary-d7tlsowag-4438488-gmailcoms-projects.vercel.app.
  Deployment `dpl_GrZNKZyWdy4gjAZ4rLFLrZXWs2jj` reached READY; health returned
  204, assets/login returned 200, and login CSRF/secure cookie flags were checked.
  This is startup evidence, not a full production CRUD/media/recovery acceptance.
- The Vercel entrypoint now supports fail-closed Production startup and a separate
  health-only R2 diagnostic; only isolated Preview startup disables mail.
- Local filesystem remains the default; the R2 adapter, private signed reads,
  owner-scoped upload tickets and direct browser staging uploads are implemented.
  Hosted synthetic R2 upload/read/range/retention/restore/cleanup passed using
  saved Vercel secrets. Browser behavior and coordinated DB recovery remain unverified.
- Provider-specific application references and the obsolete deployment runbook were removed.
- R2 quality checks, the disposable PostgreSQL migration/schema/baseline job and
  Vercel image build passed locally. See `docs/R2_MEDIA.md` for remaining live
  verification and storage-cost/recovery caveats.

## Retained decisions

- Keep Symfony/FrankenPHP, Doctrine, Twig and Vue islands.
- Use the existing Vercel project, existing Neon database, and private Cloudflare
  R2 media storage. Do not introduce a replacement framework or another permanent DB.
- Keep the existing local development database, users and original archives.
  Local email stays in Mailpit; automatic hosted/local synchronization remains on hold.
- Keep live media separate from the restic backup bucket.
- Preserve product limits: images 10 MiB, videos 100 MiB; treatments accept images only.
- Database recovery requirement remains six hours. Deleted/replaced media must
  remain recoverable for at least that period after removal.

## 1. R2 setup — user

- [ ] Create or identify a private Standard bucket; suggested name `dogs-diary-media`.
- [ ] Keep public access disabled and create bucket-scoped Object Read & Write
  credentials. Save keys privately in the password manager and Vercel Production.
- [x] Verify that all five `MEDIA_R2_*` names exist in Vercel Production and both
  key variables are sensitive (metadata only; values/access remain unverified).
- [ ] Record the exact S3 endpoint and intended production HTTPS origin.
- [ ] Apply origin-specific CORS from [R2_MEDIA.md](docs/R2_MEDIA.md).
- [ ] Leave lifecycle policies unchanged until retained/staging prefixes are implemented.

The setup guide includes the implemented `MEDIA_R2_*` variable names. Credentials
alone do not switch an existing deployment or authorize
production deployment. Only bucket name, endpoint and origin belong in chat.

## 2. R2 integration — implementation

- [x] Introduce an R2 adapter while retaining local filesystem storage for development.
- [x] Replace the filesystem-only `resolvePath()` download dependency with an
  abstraction supporting owner-authorized, short-lived signed downloads.
- [x] Add authenticated, CSRF-protected upload initiation/finalization and expiring
  owner/target-bound tickets. Upload bytes directly to R2 staging from the browser.
  Vercel documents a 4.5 MB Function payload limit; the old upload proxy cannot
  preserve our image/video limits.
- [x] Validate actual size, MIME, image/container signatures and sanitized names
  on finalization. Store validated bytes under immutable canonical keys;
  an old staging PUT URL must not overwrite accepted media.
- [x] Define replay/idempotency, interrupted uploads, database compensation,
  upload quotas and cleanup of abandoned staging objects.
- [x] Preserve dog media CRUD, thumbnail/profile selection, owner isolation and
  concurrent treatment-photo replacement. Update both Vue media repositories.
- [x] Adapt media audit to paginated remote keys and protect retained/staging
  objects from ordinary orphan deletion.
- [x] Implement delete/replace retention and a media recovery procedure matching
  the database recovery window before enabling expiration rules.
- [x] Rehearse retained-object restoration on synthetic R2 objects.
- [ ] Review staging-cost exposure before enabling lifecycle rules or opening
  public registration; coordinated DB/media recovery remains separate.

Acceptance tests cover valid/invalid files, 10/100 MiB boundaries, foreign and
expired tickets, replay, staging overwrites, replacement concurrency, video ranges,
URL expiry, database failures, and deletion/recovery. Frontend tests follow
`tests/JavaScript/TESTING.md`.

## 3. Production runtime — implementation and configuration

- [x] Replace the Preview-only entrypoint with production startup and tests;
  remove obsolete Preview-specific rules only after their production replacements.
- [ ] Read real mail settings, stable Production `APP_SECRET`, canonical database
  URL with TLS, explicit `APP_BASE_URL`, `PORT=8080` and verified R2 configuration.
- [x] Keep filesystem backup scheduling disabled on Vercel. Production recovery
  uses the chosen database recovery and media retention procedures.
- [x] Validate configuration before serving traffic. Run schema migrations
  separately through the direct connection; never on each runtime startup.
- [ ] Verify production mail/reset links, secure cookies, forwarded headers
  and private cache behavior. Local development continues to use its own data.

## 4. Verification, migration and release

- [x] Run `bin/check`, isolated PostgreSQL migration/schema/baseline checks from
  [CI.md](docs/CI.md), and the final Vercel image build.
- [x] Exercise R2 with bounded synthetic objects before importing private media.
- [x] Verify cross-instance runtime security using new disposable containers;
  restore and verify the original project alias after the diagnostic deployment.
- [ ] Prepare a controlled import preserving existing storage keys. Verify counts,
  sizes, SHA-256 and read-only media audit; preserve source files and archives.
- [ ] Verify database recovery settings and rehearse restoring the database with
  the corresponding retained photos/videos.
- [ ] Test authenticated CRUD/media behavior, ownership boundaries, sessions
  across instances/restarts, large files and video playback on the hosted runtime.
- [ ] Prepare production deployment, domain/DNS, rollback and final migration
  steps for review. Live data changes, deployment and DNS require authorization
  for those actions; plan updates are not a production cutover.

Production acceptance requires durable media, working email/authentication,
passing checks and a demonstrated DB/media recovery path. An unrun check is a
verification gap; the existing Preview startup smoke does not satisfy this gate.

## References and history

- [R2 media setup and implementation criteria](docs/R2_MEDIA.md)
- [Current Vercel runtime](docs/STATELESS_RUNTIME.md)
- [Production environment requirements](docs/PRODUCTION_CONFIGURATION.md)
- [Migration and private archive rules](docs/MIGRATIONS.md)
- [Configured checks](docs/CI.md)
- Historical local restore and hosting evidence remains in `docs/operations/`.

Only the following archived sections retain the original schedules and proposals.
They are superseded by the production plan above.

## Архів попереднього розкладу 12–16 жовтня

Нижче збережено первісні instructions, критерії та Monday/Tuesday records для
історії. Вони **не визначають сьогоднішній чи завтрашній порядок роботи**:
active plan — production deployment вище. Provider selection, додаткові local DB,
anonymized users, старі PostgreSQL 16 startup-команди та окремий `local-copy`
superseded поточними рішеннями/README. Не повторювати їх як нові завдання.

## Історичний локальний restore

PostgreSQL 16 dump та uploads уже завантажені й відновлені локально.
Застарілі provider-specific SSH/SCP instructions вилучено. Приватні архіви
зберігаємо; поточний запуск, єдина local DB та міграції описані в README.
Нижче — історичні restore/startup кроки, не завдання для повторного виконання.
Змінна `backup_dir` у командах означає приватний каталог із уже збереженим
`database.dump`; архіви не переносимо до репозиторію.

### 1. Restore лише в нову локальну базу

У корені проєкту, в тому самому локальному shell із `backup_dir`, запустити
тільки database і Mailpit. Звичайний app startup автоматично запускає migrations,
тому спочатку імпортувати dump, а вже потім запускати застосунок.

```sh
docker compose up -d database mailer
docker compose exec -T database createdb -U app dogs_diary_dev_20261012
docker compose exec -T database pg_restore \
  -U app -d dogs_diary_dev_20261012 \
  --no-owner --no-acl --exit-on-error --single-transaction \
  < "$backup_dir/database.dump"
```

Кожну команду виконувати лише після успішної попередньої. Якщо база вже існує,
вибрати нову назву і змінити її в усіх наступних командах. Не використовувати
`--clean`, `dropdb`, `docker compose down --volumes` або production URL.
Наявна локальна база `app` залишається окремою.

До запуску app зняти baseline: кількість users/dogs/treatments/media та migration
version у відновленій копії. Для щоденної розробки знеособити чужі emails/імена,
скинути локальні password-reset tokens і використовувати локальний тестовий
акаунт; конкретні SQL/console-команди підготувати за актуальною схемою.
Оригінальний dump зберігати приватно на зашифрованому диску, не в Git чи chat.

### 2. Запуск застосунку на імпортованій копії

Якщо звичайний Compose app уже працює, зупинити лише його (`docker compose stop app`)
для звільнення порту 8080. Зібрати development image, потім запустити окремий
app container із явним локальним URL та окремим `var` volume:

```sh
docker compose build app
docker compose run --rm --no-deps --service-ports \
  --name dogs-diary-local-copy \
  --volume dogs_diary_dev_20261012_var:/app/var \
  -e DATABASE_URL='postgresql://app:!ChangeMe!@database:5432/dogs_diary_dev_20261012?serverVersion=16&charset=utf8' \
  -e APP_ENV=dev \
  -e APP_BASE_URL=http://localhost:8080 \
  -e MAILER_DSN=smtp://mailer:1025 \
  -e BACKUP_SCHEDULE_ENABLED=0 \
  app
```

Цей процес працює у foreground; наступні перевірки виконувати в іншому terminal:

```sh
docker exec dogs-diary-local-copy php bin/console doctrine:schema:validate
docker exec dogs-diary-local-copy php bin/console app:media:audit
```

Відкрити `http://localhost:8080`, перевірити login локальним акаунтом, список собак
і treatments; reset email має потрапляти тільки в Mailpit (`http://localhost:8025`).
Автоматичні migrations на startup змінюють лише нову локальну копію; записати,
які застосувалися. Для тривалої роботи оформити цей URL в окремому локальному
Compose override: поточний `compose.yaml` задає `DATABASE_URL` явно, тому однієї
зміни `.env.local` недостатньо.

DB dump **не містить photos/videos**. За потреби окремо завантажити `/app/var/uploads`
з application service та скопіювати в `/app/var/uploads` контейнера
`dogs-diary-local-copy`, зберігши відносні шляхи й доступ для `www-data`.
Це окремий локальний volume, не production uploads і не старий development volume.
Відсутні файли після DB-only restore очікувані; audit запускати без `--delete-orphans`.
Для узгодженого recovery backup DB та uploads потрібне спільне вікно без записів
або чинний capture lock; незалежні копії для development не називати paired backup.

**Готово:** dump прочитаний і відновлений, локальний app працює з окремою базою,
листи йдуть у Mailpit, media limitations записані. Після перевірки зберегти
зашифровану копію dump поза тимчасовим каталогом; видалити лише конкретні
тимчасові export-файли, коли підтверджено наявність придатної копії.

## Новий напрям — переїзд на Vercel

Корекція від 11 жовтня: наступний тиждень присвячений міграції, а не подальшому
налагодженню backups на попередньому хостингу. Причина зміни — завершення trial і вимога
залишитися на безкоштовних тарифах. Найпершим кроком міграції було:
забрати DB і запустити її локально. До завершення доступу також забрати всі uploads.

Цільова схема:

| Частина | Рішення |
| --- | --- |
| Symfony + Twig + Vue islands | Vercel; перевірити Docker/FrankenPHP deployment на доступному безкоштовному тарифі |
| PostgreSQL | Обрати сервіс із постійним free tier та безкоштовним автоматичним recovery |
| Фото та наявні відео | Приватний Cloudflare R2 bucket для live media |
| Development | Наявний Docker Compose і окрема локальна DB |
| Recovery | Перевірений restore DB та окрема стратегія відновлення media |

Не переписувати app на Next.js заради hosting. Поточний Symfony backend,
owner-scoped API та Vue islands залишаються основою.

## Умови вибору безкоштовних сервісів

Стан документації перевірено 11 жовтня; перед створенням ресурсів повторно
перевірити dashboard, доступність функцій і чинні ліміти. Free tier має бути
постійним, а не trial. Жоден paid upgrade не робити автоматично.

- **Vercel:** є офіційний guide для Symfony/FrankenPHP через Docker. У понеділок
  перевірити доступність цього deployment path саме на нашому free account.
  Hobby дозволяє personal/non-commercial use; підтвердити, що проєкт відповідає
  цій умові. Наявність guide сама по собі не доводить безкоштовність усіх функцій.
  [Symfony guide](https://vercel.com/kb/guide/symfony-php-with-docker),
  [Hobby](https://vercel.com/docs/plans/hobby).
- **Neon Free — перший кандидат, ще не остаточний вибір.** Поточна пропозиція:
  1 GB DB storage на проєкт, 100 CU-hours на місяць і 6-hour instant restore.
  Це коротке автоматичне recovery window, не щоденні архіви за тиждень.
  Перевірити фактичний restore window/додаткові обмеження в створеному проєкті
  та провести restore drill.
  [Оновлення Neon Free від 2 жовтня](https://neon.com/blog/neon-free-plan-1-gb-per-project).
- **Supabase Free не задовольняє вимогу managed daily backups:** вони доступні
  на платних планах; free tier потребує власних exports.
  [Backup policy](https://supabase.com/docs/guides/platform/backups).
- **R2 Standard:** безкоштовно в межах 10 GB-month storage, 1 million Class A
  і 10 million Class B operations щомісяця. Поза лімітами є оплата; це не
  unlimited storage. Врахувати разом live media, retained copies і backups.
  [R2 pricing](https://developers.cloudflare.com/r2/pricing/).

У понеділок явно визначити потрібну retention: чи достатньо 6 годин, чи потрібен
restore учорашнього/тижневого стану. Для порівняння DB-провайдерів записати:
storage/compute/connection limits, sleep behavior, регіон, сумісну PostgreSQL
версію, retention/RPO, автоматичність, платність самого restore і результат drill.
Реплікація або слово «durable» не є backup, доступним користувачу.

Якщо потрібна довша історія, а відповідний free provider не знайдений, назвати
це невиконаною вимогою. Окремо оцінити free DB + власний scheduled export у R2
в межах безкоштовних квот runner/storage; не підміняти цим managed auto backups
без рішення користувача і не повертати приховано багатотижневу backup-розробку.

## Понеділок, 12 жовтня — забрати дані та перевірити Vercel

### Зроблено

- [x] PostgreSQL 16 dump із попереднього хостингу завантажено і відновлено локально. Схема,
  counts та browser flow reset → login → dogs/treatments/media перевірені.
- [x] Усі uploads збережено окремо: 4 файли, 1 650 585 bytes; manifest містить
  відносні шляхи, розміри та SHA-256. Хеші відновлених файлів збіглися.
- [x] Backup збережено поза тимчасовими каталогами на FileVault-захищеному Mac;
  private directory/files permissions перевірені. Dump і media знято окремо,
  тому це development copy, а не coordinated recovery snapshot.
- [x] Записано source commit, migration versions, DB size 8 322 071 bytes,
  domain/nameservers, mail transport та env variable names без secret values.
- [x] Підготовлено `Dockerfile.vercel`, `vercel.json`, Preview-only entrypoint;
  локально перевірено build, PHP extensions, AssetMapper, Twig та DB connection.
  `/healthz` → 204, `/login` → 200. Hosted deployment цим не підтверджено.

### Ще не завершено

- [ ] Реальний Vercel Preview deployment і remote smoke; доступність container
  runtime саме на безкоштовному account та personal/non-commercial eligibility.
- [ ] DB provider/region/quotas/retention: потрібне рішення 6 годин / 24 години /
  7 днів. Neon — лише кандидат, не обраний і не provisioned provider.
- [ ] Перевірка Cloudflare origin DNS та підтвердження збереження secrets у
  password manager. Public DNS показує proxy, не фактичний origin.
- [ ] Точний shutdown deadline попереднього хостингу: API показав 0 trial days і кінець billing
  period `2026-10-12T23:59:59.999Z`, але це не гарантія часу видалення/retention.

### Початкові задачі дня (для зіставлення)

1. Виконати перший розділ: source DB → dump → нова local DB → app smoke.
   Записати точний trial expiry та deadline експорту. Якщо доступ закінчується
   раніше понеділка, export треба перенести раніше; не розраховувати на збереження
   volumes попереднього хостингу після завершення доступу.
2. Завантажити всі uploads із web service окремо; зберегти private archive,
   storage-key-relative paths, кількість файлів, bytes і SHA-256 manifest.
   Photos/videos не містяться у PostgreSQL dump.
3. Зафіксувати commit/schema version, розмір DB, media inventory, domain/DNS,
   mail transport і потрібні environment variable names. Секрети зберігати
   в password manager; не комітити й не публікувати.
4. До 2 годин: spike deployment поточного Symfony/FrankenPHP у Vercel Preview
   з synthetic data. Підготувати `Dockerfile.vercel` / `vercel.json`, перевірити
   PHP 8.4 extensions, AssetMapper assets, `/healthz`, Twig page і DB connection.
   Preview не підключати до production DB/R2.
5. Перевірити Vercel Hobby eligibility і доступність Docker runtime без upgrade.
   Якщо шлях недоступний без оплати, зафіксувати blocker та запропонувати
   альтернативний runtime/hosting із конкретними tradeoffs; не починати rewrite.
6. Обрати DB provider за критеріями вище; Neon — перший для перевірки.
   Створення ресурсів і deployments виконувати під час реалізації, не підготовки плану.

**Запланований результат (ще не повністю досягнутий):** дані збережені незалежно від попереднього хостингу, local development працює, Vercel feasibility
і DB recovery requirements перевірені. Подальший secret debugging попереднього хостингу знято
з активного тижня; для експорту вирішувати лише конкретні access blockers.

## Вівторок, 13 жовтня — PostgreSQL та stateless runtime

### Зроблено

- [x] Production sessions, login rate-limit state та locks перенесено у PostgreSQL;
  ключі стабільні між builds, TLS connection options збережені, помилки limiter
  storage не обходять обмеження. Redis/додатковий сервіс не додавали.
- [x] Два ізольовані локальні instances пройшли login/CSRF/logout/reset,
  restart persistence, session expiry та concurrent login throttling smoke.
- [x] Виконано локальний restore rehearsal понеділкового dump із перевіркою
  counts і схеми; production та Preview images зібрані. Це не managed PITR drill.
- [x] Посилено Preview preflight: hosted DB TLS, secure cookies, mail off;
  production startup цим image відхиляється, migrations/scheduler на Preview
  startup не запускаються. Для майбутнього paired recovery визначено write-pause
  / drain; cloud implementation цього механізму ще немає.
- [x] Vercel agent setup завершено: global CLI 63.1.2 авторизований, Codex plugin
  enabled, shared MCP `https://mcp.vercel.com` підключений. Documentation search
  і authenticated `list_teams` пройшли (повернуто 0 teams). Project/deployment
  під час setup не створювали; Vercel login більше не є попереднім blocker.
- [x] На прохання користувача залишено одну локальну DB із 2 dogs; Compose та
  host `.env` спрямовані на неї, uploads volume збережено, Mailpit на 18025.
- [x] Видалено 5 retired containers, Tuesday test network/DB volume, порожню
  spike DB і стару `app` DB/volume. Стару DB попередньо архівовано у
  `~/DogsDiaryBackups/retired-local-2026-10-13`; її uploads directory вже був
  порожній. Unrelated Docker resources та Monday backups не чіпали.
- [x] Виконано squash 5 → 1 migration. Pre-squash recovery dump збережено у
  `~/DogsDiaryBackups/pre-squash-2026-10-13`; fingerprints усіх 7 domain tables
  до/після збіглися. Тести використовують rollback-only schemas у тій самій DB;
  додаткових databases/volumes або test schemas не залишилось.
- [x] Перевірено commit `5784bac` (Cleanup), виправлено EOF whitespace;
  `dev` rebased onto `main` із збереженням новіших Preview safety checks.
  Rebase commit — `77e10bc`; push під час rebase не виконувався.

### Перевірки та межі результату

- `bin/check`: **passed**, 98 PHP tests / 831 assertions, 4 JavaScript tests,
  11 Python tests, Composer validation/audit, formatting, YAML/Twig/container lint.
- `docker compose exec -T app php tests/Migrations/verify_baseline.php`:
  **passed**, 8 scenarios (fresh/Monday/current та відхилення некоректних станів).
- `docker compose exec -T app php bin/console doctrine:migrations:status` та
  `doctrine:schema:validate`: **passed**, один baseline і схема синхронна.
- `docker compose exec -T app php bin/console app:media:audit`: **passed**,
  немає missing/orphan files. Local HTTP smoke: health 204, login 200.
- Cross-instance smoke пройдено **до cleanup/squash**; його test containers
  видалені. Після rebase перевірено shell syntax, JSON та whitespace; повний
  test suite після самого rebase не запускався. Remote CI не перевірено.

### Ще не завершено

- [x] Neon Free у Frankfurt створено; Monday dump імпортовано у єдину hosted
  `neondb`, baseline застосовано. Direct `verify-full` TLS перевірено; Preview
  використовує synthetic schema та restricted role без доступу до `public`.
- [x] Local PostgreSQL оновлено 16 → 18.6 через fresh-volume dump/restore.
  Усі 11 tables мають ті самі counts/fingerprints; uploads і local credentials
  збережені. Старий inactive volume і новий приватний dump лишено для rollback.
  Є одна активна local application DB та одна hosted application DB.
- [x] Користувач погодив hosted + local topology, безкоштовне 6-hour recovery
  та personal/non-commercial Hobby use.
- [ ] Hosted Preview, live pooled load/connection-limit та HTTP verification.
- [ ] Provider-managed recovery drill, фактичне retention і підтвердження $0.
- [ ] Repeatable hosted → local sync із backup перед заміною local data.
  Automatic sync **відкладено на прохання користувача**; нічого не увімкнено.
- [x] На окреме прохання відновлено оригінальний Monday dump локально, без
  anonymization users/password hashes. Усі 7 domain tables збігаються з backup
  до/після baseline migration; uploads не змінювались. Попередній local DB
  збережено у приватному pre-restore dump. [Restore record](docs/operations/2026-10-14-local-original-restore.md).

> Продовження 14 жовтня: [hosting record](docs/operations/2026-10-14-hosting.md).
> Створено та linked Vercel project `dogs-diary` на підтвердженому Hobby account;
> Preview secret/port та restricted pooled DB URL задані. Local app і hosted DB
> тепер на PostgreSQL 18.6, domain data fingerprints збігаються; якісні checks,
> migration/schema та media audit пройдені. Vercel first-deploy Preview був
> несподівано classified Production і негайно видалений. Для наступного Preview
> потрібна згода на protected static setup-only Production bootstrap без DB/domain
> changes; managed recovery drill очікує `neon login`. Hosted runtime не запущений.

### Початкові задачі дня (hosted частина залишається планом)

1. Імпортувати dump у нову isolated DB/branch обраного провайдера, не поверх
   production. Порівняти table counts і schema; migrations проганяти окремо
   від container startup. Runtime — pooled connection за потреби, migration/dump —
   відповідне direct connection; TLS та ліміти Doctrine connections перевірити.
2. Провести provider-managed recovery drill на disposable data: створити запис,
   змінити/видалити його й відновити попередній стан в окрему DB/branch.
   Записати restore point, тривалість, retention і підтвердження вартості $0.
3. Винести sessions та спільний rate-limit state із локальних файлів у стійке
   сховище; спочатку оцінити PostgreSQL, щоб не додавати зайвий платний сервіс.
   Перевірити login/CSRF/logout/reset-password після restart та між instances.
4. Переглянути `config/packages/framework.yaml`, cache/log paths, trusted proxies,
   HTTPS cookies, `APP_BASE_URL`, mailer і production preflight під Vercel.
   Production secrets не мають потрапляти у Preview.
5. Не переносити `ops/backup.py serve` як постійний scheduler у Vercel.
   Поточний filesystem capture lock також не координує кілька instances:
   для нового recovery workflow визначити інший consistency mechanism.

**Запланований результат (ще не досягнутий у hosting):** preview app працює з новою DB, а автоматичне відновлення
продемонстроване, не лише обіцяне тарифною сторінкою.

## Середа, 14 жовтня — photos/videos у Cloudflare R2

1. Створити окремий приватний live-media bucket. Не використовувати restic
   repository `production/` у backup bucket як live storage. Надати app
   credentials лише до потрібного bucket; зберегти їх до sealing.
2. Реалізувати R2 storage adapter за чинним `MediaStorageInterface`.
   Інтерфейс зараз містить `resolvePath()`, тому перевірити й змінити споживачів
   локальних шляхів: `PrivateMediaResponseFactory`, download controllers,
   upload validation та media audit. Однієї заміни env variable недостатньо.
3. Зберегти owner authorization, приватні downloads, MIME/size validation,
   treatment-photo replacement і delete semantics. Не робити bucket публічним.
4. Виміряти upload/download limits саме обраного Vercel runtime. Наявні ліміти app:
   images 10 MB, videos 100 MB. Якщо proxy через Function їх не підтримує,
   реалізувати authenticated presigned upload + server-side finalize/validation;
   не знижувати продуктові ліміти мовчки. Перевірити CORS, range/video downloads,
   прострочені URLs, чужі object keys, повторний finalize та orphan cleanup.
5. Перенести файли, зберігши storage keys; звірити counts/checksums. Preview
   використовує тестовий bucket/prefix, де deletion не зачіпає live media.
6. Окремо визначити recovery media: R2 як основне сховище не є незалежною копією.
   Узгодити retention видалених/замінених objects із DB restore window та
   перевірити відновлення фото після видалення; врахувати storage quota.

**Результат:** create/view/replace/delete media працює в Preview, файли не залежать
від локального volume попереднього хостингу, а DB restore не повертає лише посилання на вже втрачені фото.

## Четвер, 15 жовтня — rehearsal, CI та остаточний cutover

1. Прогнати `bin/check`, isolated PostgreSQL migration/schema checks із
   [docs/CI.md](docs/CI.md), build нового deployment image та Preview smoke.
   Перед frontend test changes прочитати `tests/JavaScript/TESTING.md`.
2. Перевірити login/logout/reset, dog/treatment CRUD, image/video upload,
   чужий resource → 404, expiry session і restart. Виконати recovery rehearsal
   DB + media та записати, які дані можна відновити і за який час.
3. До cutover: CI, free-plan eligibility, DB automatic restore і R2 checks мають
   пройти. Якщо ні — не оголошувати міграцію завершеною й не видаляти джерело.
4. У погоджене вікно призупинити mutations на source hosting, виконати final DB dump
   і final media sync, перевірити counts/checksums; зафіксувати один source of truth.
5. Імпортувати final DB у ціль, виконати потрібні migrations окремим release step,
   підключити Vercel Production до final DB/R2, налаштувати domain/DNS і HTTPS.
   Нові записи дозволити лише на Vercel після smoke; старий app лишається без writes.
6. Rollback: до відкриття writes можна повернути старий app за збереженим станом.
   Після нових writes у цілі спочатку зупинити записи і перенести/узгодити delta;
   простий DNS rollback на застарілу source DB втратить дані.
   Якщо попередній хостинг уже недоступний, rollback target — попередній сумісний Vercel
   deployment зі збереженими DB/R2; database rollback потребує окремого restore plan.

**Результат:** production на Vercel або точний migration blocker. DNS change,
production deploy і переключення записів — окремі погоджені операції.

## П'ятниця, 16 жовтня — перевірка після переїзду та повернення до v2

1. Перевірити errors, latency/cold start, DB compute/storage і R2 operations;
   зафіксувати фактичні free quotas, alerts і поведінку при їх вичерпанні.
2. Повторно перевірити доступні automatic restore points та media recovery.
   Зберегти offline encrypted final export і перевірений recovery runbook.
3. Після підтвердження роботи нової системи погодити вимкнення колишніх hosting resources
   та billing. Не залишати платні services/volumes випадково; не видаляти єдину
   recovery copy. Зафіксувати, як довго джерело ще доступне без оплати.
4. Оновити README/deployment docs: Vercel, DB provider, R2, local workflow,
   migrations, backup/restore, costs/limits та rollback.
5. Решту часу повернути v2: одна мала локальна задача. Якщо migration виявилася
   більшою, явно перенести feature work, а не обіцяти неперевірений Friday release.

## Definition of done тижня

- [ ] DB вручну завантажена та працює локально; усі uploads збережені незалежно від попереднього хостингу.
- [ ] Vercel hosting працює на прийнятному free tier без залежності від trial.
- [ ] DB provider обраний; free automatic recovery і його retention підтверджені drill.
- [ ] Якщо 6-hour recovery недостатньо, рішення прийнято явно, вимогу не послаблено мовчки.
- [ ] Photos/videos у приватному R2, права доступу й усі чинні upload flows збережені.
- [ ] Перевірено відновлення DB та відповідних media.
- [ ] Sessions/rate limits/recovery не покладаються на ephemeral local files.
- [ ] CI та production smoke пройшли; final sync і cutover не втратили writes.
- [ ] Колишні hosting resources/billing завершені погоджено, recovery copies збережені.
- [ ] Зафіксовані free quotas, owners, runbooks і наступна v2 задача.

Restore/startup-команди вище — історичний PG16 workflow, не інструкція для повторного
запуску нинішнього stack. Експорт із попереднього хостингу і local restore вже виконані та
перевірені; поточні PG18/local-account instructions у README та restore record.
Джерела PostgreSQL export/restore:
[pg_dump 16](https://www.postgresql.org/docs/16/app-pgdump.html),
[pg_restore 16](https://www.postgresql.org/docs/16/app-pgrestore.html).

---

# Історичний план на 14–18 вересня 2026 — збережений backlog

Нижче збережено попередній план без зміни його змісту. Він не є активним
розкладом на 12–16 жовтня; актуальність кожної задачі перевіряємо за кодом.

## Мета тижня

Після першого production deployment закрити найважливіші operational і
архітектурні ризики: перевірені backups, безпечну публічну реєстрацію, однозначну
модель ownership, легший dog-list endpoint і базовий browser safety net.

Увесь тиждень є одним milestone, але зміни викочуємо в production поступово —
трьома невеликими тематичними релізами. Кожен deploy виконуємо лише після
проходження CI та короткого ручного smoke test.

## Стратегія релізів

Не збираємо всі покращення в один великий реліз. Окремі невеликі deploys легше
перевіряти, діагностувати й безпечно відкочувати. Особливо не змішуємо database
migration ownership із API або frontend-змінами.

### Operations — без релізу застосунку

- Увімкнути backups.
- Провести restore drill.
- Налаштувати monitoring і read-only media audit.

Ці дії захищають production перед наступними змінами, але не потребують нового
application image.

### Release 1 — email verification

- Verification flow, resend і throttling.
- Doctrine migration для verification state/token.
- Backend tests і ручний auth smoke test.

Після deploy перевірити sign-up, verification, login і reset password та дати
релізу попрацювати окремо перед наступною schema change.

### Release 2 — single-owner migration

- Production data preflight.
- Перехід від many-to-many до одного owner.
- Ownership і destructive-operation tests.

Це найризикованіший реліз тижня, тому перед ним обов'язковий manual backup. У
ньому не повинно бути unrelated API, performance або frontend changes.

### Release 3 — API optimization і UI quality

- Dog summary/detail representation.
- Immutable treatment dates.
- Modal accessibility.
- Browser smoke tests.

Browser tests самі по собі не потребують окремого deploy: вони є release gate
для цього релізу.

## Понеділок — backups, recovery та monitoring

### Результат дня

Production-дані мають не просто зберігатися на volumes, а мати перевірений шлях
відновлення.

### Роботи

1. Завершити PR із локальним Docker workflow, щоб усі подальші команди
   виконувалися в однаковому PHP 8.4 + FrankenPHP середовищі.
2. Для PostgreSQL налаштувати перевірені backups:
   - daily backups;
   - weekly backups;
   - ручний backup перед restore drill.
3. Увімкнути daily та weekly backups для `/app/var/uploads` volume.
4. Якщо обраний hosting plan не дозволяє scheduled backups, зафіксувати це як
   blocker і того ж дня обрати одне з двох рішень:
   - перейти на plan із backups;
   - тимчасово налаштувати щоденний offsite `pg_dump` і окрему копію uploads.
5. Зробити restore drill у scratch database/volume, не замінюючи production:
   перевірити міграції, кількість users/dogs/treatments/media та кілька останніх
   записів.
6. Додати зовнішню uptime-перевірку `/healthz` і сповіщення про `5xx` або
   недоступність сайту.
7. Запланувати read-only `app:media:audit`; видалення orphan files не вмикати.

### Definition of done

- Є хоча б один успішний backup PostgreSQL і uploads.
- Restore перевірений поза production, записані дата, тривалість і результат.
- `/healthz` моніториться зовнішнім сервісом.
- У deployment guide записані фактичні налаштування й recovery steps.

## Вівторок — email verification і захист sign-up

### Результат дня

Новий акаунт не отримує повний доступ, доки користувач не підтвердить email.

### Роботи

1. Додати до User стан підтвердження email і відповідну Doctrine migration.
2. Реалізувати одноразовий verification token із терміном дії.
3. Надсилати verification email через чинний Resend HTTPS transport.
4. Додати сторінки/стани:
   - «перевірте пошту»;
   - успішне підтвердження;
   - прострочене або некоректне посилання;
   - повторне надсилання листа.
5. Додати rate limiting для sign-up та resend verification.
6. Не розкривати, чи вже існує акаунт із заданим email.
7. Покрити flow backend-тестами, включно з одноразовістю і строком дії token.

### Definition of done

- Непідтверджений користувач не може відкрити diary/API.
- Verification link одноразовий і має expiry.
- Повторне надсилання throttled.
- Локальні листи видно в Mailpit, production transport працює через Resend.
- PHPUnit і CI зелені.
- Release 1 окремо розгорнутий і пройшов production auth smoke test.

## Середа — однозначна модель ownership і deletion policy

### Рекомендоване рішення

Поки sharing не є заявленою функцією, перейти від `Dog ↔ User` many-to-many до
одного обов'язкового owner (`Dog → User` many-to-one). Це прибирає приховану
можливість, за якої один owner може видалити спільну собаку для всіх.

Якщо цього дня вирішуємо будувати sharing, migration не робимо: спочатку окремо
проєктуємо invitations, ролі owner/editor/viewer, last-owner rule і право на
видалення. Повний sharing не намагаємося втиснути в цей тиждень.

### Роботи для single-owner варіанта

1. Перевірити production `dog_owner`: у кожної собаки має бути рівно один owner.
2. Створити forward-only migration до `dog.owner_id` із foreign key та index.
3. Перенести наявні зв'язки без втрати даних; migration має зупинитися з ясною
   помилкою, якщо знайдено zero/multiple owners.
4. Оновити entities, repositories, services і ownership isolation tests.
5. Визначити deletion policy для першої версії:
   - dog і treatment видаляються назавжди лише після явного підтвердження;
   - account deletion не реалізовувати без export/retention рішення;
   - перед destructive schema changes створювати manual backup.
6. Прогнати migration dry-run, clean PostgreSQL migration chain і rollback
   rehearsal на disposable database.

### Definition of done

- У моделі немає неіснуючої product-функції shared ownership.
- Production data preflight не виявляє неоднозначних owners.
- Ownership та destructive operations покриті тестами.
- Migration перевірена на чистій і на копії існуючої бази.
- Release 2 не містить unrelated API/frontend змін і розгорнутий лише після
  manual backup.

## Четвер — dog summary/detail та treatment consistency

### Результат дня

Список собак не завантажує treatment history для кожної картки, а моделі дат
використовують узгоджену immutable семантику.

### Роботи

1. Розділити `DogView` на list summary і detail representation.
2. Для `GET /api/dogs` повертати лише дані картки та thumbnail/profile preview,
   без treatments.
3. Залишити treatments у detail endpoint або завантажувати їх через уже наявний
   treatment endpoint.
4. Додати query/response regression tests, які не дозволять випадково повернути
   treatments у collection response.
5. Перевести treatment/due dates з mutable `DateTime` на `DateTimeImmutable` з
   безпечною migration і тестами серіалізації/валідації.
6. `SIMPLE_ARRAY` для treatment types цього дня лише документуємо як свідоме
   display-only рішення. Нормалізацію відкладаємо, доки не з'явиться пошук або
   фільтрація за типом.

### Definition of done

- Collection response не містить treatments.
- Кількість database queries для dog list не росте на одну treatment-query для
  кожної собаки.
- List/detail UI продовжують працювати без зміни user-visible поведінки.
- Усі treatment dates immutable.

## П'ятниця — browser safety net, modal accessibility і release

### Результат дня

Критичний user journey перевіряється автоматично, а модальні вікна коректно
працюють із клавіатурою.

### Роботи

1. Перед зміною frontend tests повністю перечитати
   `tests/JavaScript/TESTING.md` і оновити його разом із test scripts, якщо
   додається новий runner.
2. Додати мінімальний browser smoke suite:
   - sign-up → verification → login → logout;
   - reset password;
   - create dog → edit → treatment → delete;
   - upload/select media;
   - чужий resource повертає безпечний 404;
   - expired session повертає користувача на login.
3. Завершити `ModalDialog`:
   - focus trap;
   - початковий focus;
   - повернення focus на opener;
   - Escape/backdrop behavior;
   - scroll lock;
   - inert background.
4. Додати observable-behavior tests для modal keyboard behavior.
5. Прогнати повний quality gate у контейнерах:
   - PHPUnit;
   - frontend tests;
   - PHP CS Fixer dry-run;
   - YAML/Twig/container lint;
   - Doctrine schema validation;
   - clean PostgreSQL migration chain;
   - production Docker build.
6. Провести staging/production smoke test і лише після цього створити tag та
   виконати Release 3.

### Definition of done

- Критичний auth/diary flow має автоматичний browser smoke.
- Modal повністю керується клавіатурою та відновлює focus.
- CI зелений, production image збирається, migrations проходять на чистій БД.
- Після deploy `/healthz`, sign-up, verification, login і reset password
  працюють без `5xx`.

## Не включаємо в цей тиждень

- Повний sharing між користувачами.
- Pagination, доки реальні collection sizes залишаються малими.
- Object storage і кілька application replicas.
- Malware scanning або повний video decode.
- Повний account export/deletion workflow.
- Загальну локалізацію інтерфейсу.

Ці пункти переглядаємо після стабілізації production і появи відповідних
product/scale вимог.

## Порядок PR

1. Local Docker workflow.
2. Backup/operations documentation та monitoring configuration.
3. Email verification і sign-up protection.
4. Single-owner migration та deletion policy.
5. Dog summary/detail і immutable treatment dates.
6. Browser smoke та modal accessibility — разом із Release 3.

Кожен PR має бути самодостатнім, із migration notes (де потрібно), тестами та
чітким rollback/restore plan для змін, що торкаються production data.

PR не обов'язково дорівнює production release: кілька тісно пов'язаних PR можна
включити в один із трьох тематичних релізів, але release boundaries вище не
об'єднуємо.
