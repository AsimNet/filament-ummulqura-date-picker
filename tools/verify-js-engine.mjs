#!/usr/bin/env node
/*
 * Cross-checks the JS Umm al-Qura engine in dist/hijri-date-time-picker.js
 * against golden data produced by the PHP package. Usage:
 *
 *   php tools/generate-golden.php > /tmp/ummulqura-golden.json
 *   node tools/verify-js-engine.mjs /tmp/ummulqura-golden.json
 */
import { readFileSync } from 'node:fs'

const {
    createUmmAlQuraEngine,
    rjdFromGregorian,
    gregorianFromRjd,
} = await import('../dist/hijri-date-time-picker.js')

const golden = JSON.parse(readFileSync(process.argv[2], 'utf8'))

const engine = createUmmAlQuraEngine({
    epoch: golden.epoch,
    monthLengths: golden.monthLengths,
    minYear: golden.minYear,
})

let failures = 0

const assertEqual = (actual, expected, label) => {
    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        failures++
        console.error(`FAIL ${label}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`)
    }
}

for (const pair of golden.pairs) {
    const [hy, hm, hd, gregorian] = pair
    const [gy, gm, gd] = gregorian.split('-').map(Number)

    const rjd = engine.toRjd(hy, hm, hd)
    assertEqual(gregorianFromRjd(rjd), { year: gy, month: gm, day: gd }, `toGregorian(${hy}-${hm}-${hd})`)

    assertEqual(
        engine.toHijri(rjdFromGregorian(gy, gm, gd)),
        { year: hy, month: hm, day: hd },
        `toHijri(${gregorian})`,
    )
}

for (const [hy, hm, length] of golden.monthLengthSamples) {
    assertEqual(engine.monthLength(hy, hm), length, `monthLength(${hy}-${hm})`)
}

assertEqual(engine.minRjd, golden.minRjd, 'minRjd')
assertEqual(engine.maxRjd, golden.maxRjd, 'maxRjd')
assertEqual(engine.toHijri(engine.minRjd - 1), null, 'toHijri below range')
assertEqual(engine.toHijri(engine.maxRjd + 1), null, 'toHijri above range')

if (failures > 0) {
    console.error(`${failures} failure(s) across ${golden.pairs.length} pairs`)
    process.exit(1)
}

console.log(`OK: ${golden.pairs.length} conversion pairs, ${golden.monthLengthSamples.length} month lengths, range bounds all match PHP.`)
