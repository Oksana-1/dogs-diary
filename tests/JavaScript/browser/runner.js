import 'qunit';

const { QUnit } = window;
QUnit.config.autostart = false;
QUnit.config.testTimeout = 3000;
const failures = [];
QUnit.on('testEnd', result => {
    for (const error of result.errors) {
        failures.push({ test: result.name, message: error.message, actual: error.actual, expected: error.expected, stack: error.stack });
    }
});
QUnit.on('runEnd', result => {
    // An earlier module/resource error must never be overwritten by a green run.
    if (document.getElementById('browser-test-result').textContent !== 'pending') return;
    window.reportBrowserTests({
        status: result.status,
        tests: result.testCounts,
        failures,
    });
});

try {
    await import('../modules/dogsDiary/view/ui/modals/ConfirmModal.test.js');
    QUnit.start();
} catch (error) {
    window.reportBrowserTests({status: 'error', message: String(error)});
}
