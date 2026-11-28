import { existsSync } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

export const repositoryRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
export const testingDirectory = path.join(repositoryRoot, 'storage', 'framework', 'testing');
export const defaultDatabase = path.join(testingDirectory, 'smv-browser.sqlite');

export function phpBinary() {
    if (process.env.SMV_PHP_BINARY) {
        return process.env.SMV_PHP_BINARY;
    }

    const herd = 'C:\\Users\\User\\.config\\herd\\bin\\php83\\php.exe';

    return process.platform === 'win32' && existsSync(herd) ? herd : 'php';
}

export function databasePath() {
    return path.resolve(process.env.SMV_BROWSER_DATABASE ?? defaultDatabase);
}

export function assertDisposableDatabase() {
    const database = databasePath();
    const relative = path.relative(testingDirectory, database);

    if (process.env.SMV_BROWSER_ALLOW_RESET !== '1') {
        throw new Error('Browser database reset refused: run through `npm run test:browser`.');
    }

    if (relative.startsWith('..') || path.isAbsolute(relative) || path.extname(database) !== '.sqlite') {
        throw new Error(`Browser database must be an isolated SQLite file below ${testingDirectory}.`);
    }

    return database;
}

export function applicationEnvironment() {
    return {
        ...process.env,
        APP_ENV: 'testing',
        APP_DEBUG: 'false',
        APP_URL: process.env.SMV_BROWSER_BASE_URL ?? 'http://127.0.0.1:4173',
        BCRYPT_ROUNDS: '4',
        CACHE_STORE: 'array',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: databasePath(),
        DB_URL: '',
        MAIL_MAILER: 'array',
        PAYMENT_GATEWAY: 'fake',
        QUEUE_CONNECTION: 'sync',
        SESSION_DRIVER: 'file',
    };
}
