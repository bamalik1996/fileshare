/**
 * Shared fast-check arbitraries (generators) for the fileshare-enhancements-bundle
 * frontend property tests.
 *
 * This is a placeholder scaffold introduced by task 1.3. Each arbitrary below
 * is declared just enough to compile against `fast-check` once the dependency
 * is installed (task 1.1). Property tests added in later tasks will fill these
 * in to match the calibration described in design.md
 * "Testing Strategy → Property Test Configuration".
 *
 * Calibration targets (mirroring tests/Support/Generators.php):
 * - URL arbitrary  (Property 1) : valid HTTPS URLs of length 1..2048.
 * - Password arb.  (Property 2) : printable strings of length 6..128.
 * - Bytes arb.     (Property 24): Uint8Array buffers of 5 MB..50 MB.
 * - Markdown arb.  (Property 37): random CommonMark plus adversarial inputs.
 * - Room code arb. (Property 15): 6-char strings over `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`.
 * - Expiry option  (Property 7) : one of `1h`, `6h`, `24h`, `7d`, plus `30d` for accounts.
 *
 * NOTE: We intentionally do not import `fast-check` here yet so that the file
 * compiles before task 1.1 installs the dependency. The TODO blocks below
 * will be replaced with real `fc.*` arbitraries once `fast-check` is on disk.
 */

export type ExpiryOption = '1h' | '6h' | '24h' | '7d' | '30d';

const NOT_IMPLEMENTED =
    'generators.ts is a placeholder. Install fast-check (task 1.1) and wire ' +
    'this arbitrary as part of the relevant property test task.';

/**
 * Random valid HTTPS URLs of length 1..2048 (Property 1).
 */
export function httpsUrl(): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * Random printable passwords of length 6..128 (Property 2).
 */
export function password(): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * Allowed expiry options (Property 7).
 * @param accountPrincipal Include `30d` when true.
 */
export function expiryOption(_accountPrincipal = false): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * Random byte buffers for chunked-upload property tests (Property 24).
 * @param minBytes Inclusive lower bound (default 5 MiB).
 * @param maxBytes Inclusive upper bound (default 50 MiB).
 */
export function bytes(
    _minBytes = 5 * 1024 * 1024,
    _maxBytes = 50 * 1024 * 1024,
): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * Random CommonMark sources plus adversarial constructions (Property 37).
 */
export function markdownSource(): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * 6-character room codes from the unambiguous alphabet (Property 15).
 */
export function roomCode(): never {
    throw new Error(NOT_IMPLEMENTED);
}

/**
 * The room-code alphabet, exported so tests can assert membership without
 * having to duplicate the literal.
 */
export const ROOM_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
