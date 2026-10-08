// Keep the same csstree/validator rule while teaching older Moodle toolchains
// current CSS syntax. Positive and negative controls prevent silently disabling it.
const {createRequire} = require('node:module');
const path = require('node:path');
const assert = require('node:assert/strict');
const fromMoodle = createRequire(path.join(process.cwd(), 'package.json'));
const stylelint = fromMoodle('stylelint');
const config = {
    plugins: [fromMoodle.resolve('stylelint-csstree-validator')],
    rules: {'csstree/validator': true},
};

async function check() {
    const valid = await stylelint.lint({
        config,
        code: `
            a {
                container-type: inline-size;
                container-name: card;
                vector-effect: non-scaling-stroke;
                font-size: clamp(1rem, 2vw, 2rem);
                color: color-mix(in srgb, red, blue);
            }
            @container card (min-width: 20rem) { a { padding: 1rem; } }
        `,
    });
    assert.equal(valid.results.flatMap(result => result.warnings).length, 0,
        'Valid modern CSS must be understood by the grammar checker');
    const invalid = await stylelint.lint({
        config,
        code: 'a { font-size: definitely-not-a-size; color: definitely-not-a-colour; }',
    });
    const errors = invalid.results.flatMap(result => result.warnings);
    assert.equal(errors.length, 2, 'The checker must reject both invalid CSS values');
    assert(errors.every(error => error.rule === 'csstree/validator' && error.severity === 'error'));
    console.log('Strict CSS grammar: modern syntax accepted; both invalid values rejected.');
}

check().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
