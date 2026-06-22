#!/usr/bin/env node
/**
 * Minify public/assets/css/custom.css -> public/assets/css/custom.min.css.
 *
 * Standalone, dependency-free minifier so the build does not require an
 * additional npm install. It performs the safe transforms only:
 *   1. Strip /* ... *\/ comments (but preserve `/*!` license blocks).
 *   2. Collapse runs of whitespace to a single space.
 *   3. Drop whitespace adjacent to structural punctuation.
 *   4. Remove the final semicolon inside a declaration block.
 *   5. Drop trailing whitespace at end of file.
 *
 * Strings (`"..."` and `'...'`) are passed through verbatim so url(...)
 * arguments and content properties survive untouched.
 *
 * Invocation: `node scripts/minify-custom-css.mjs`.
 */

import { readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";

const here = dirname(fileURLToPath(import.meta.url));
const inputPath = resolve(here, "..", "public", "assets", "css", "custom.css");
const outputPath = resolve(here, "..", "public", "assets", "css", "custom.min.css");

const source = readFileSync(inputPath, "utf8");

function stripComments(input) {
    let out = "";
    let i = 0;
    while (i < input.length) {
        const ch = input[i];
        // Preserve string literals.
        if (ch === '"' || ch === "'") {
            const quote = ch;
            out += ch;
            i++;
            while (i < input.length) {
                const c = input[i];
                out += c;
                i++;
                if (c === "\\" && i < input.length) {
                    out += input[i];
                    i++;
                    continue;
                }
                if (c === quote) break;
            }
            continue;
        }
        if (ch === "/" && input[i + 1] === "*") {
            // Preserve `/*!` license blocks.
            if (input[i + 2] === "!") {
                const end = input.indexOf("*/", i + 3);
                if (end === -1) break;
                out += input.slice(i, end + 2);
                i = end + 2;
                continue;
            }
            const end = input.indexOf("*/", i + 2);
            if (end === -1) break;
            i = end + 2;
            continue;
        }
        out += ch;
        i++;
    }
    return out;
}

function collapseWhitespace(input) {
    let out = "";
    let i = 0;
    while (i < input.length) {
        const ch = input[i];
        if (ch === '"' || ch === "'") {
            const quote = ch;
            out += ch;
            i++;
            while (i < input.length) {
                const c = input[i];
                out += c;
                i++;
                if (c === "\\" && i < input.length) {
                    out += input[i];
                    i++;
                    continue;
                }
                if (c === quote) break;
            }
            continue;
        }
        if (/\s/.test(ch)) {
            // Collapse run of whitespace to a single space.
            while (i < input.length && /\s/.test(input[i])) i++;
            out += " ";
            continue;
        }
        out += ch;
        i++;
    }
    // Drop whitespace adjacent to structural punctuation.
    out = out
        .replace(/\s*([{};:,>+~])\s*/g, "$1")
        .replace(/;\}/g, "}")
        .replace(/^\s+|\s+$/g, "");
    return out;
}

const minified = collapseWhitespace(stripComments(source));
writeFileSync(outputPath, minified + "\n");
console.log(
    `[minify-custom-css] ${inputPath} (${source.length} bytes) -> ${outputPath} (${minified.length + 1} bytes)`,
);
