import { closeSync, mkdirSync, openSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { applicationEnvironment, assertDisposableDatabase, phpBinary, repositoryRoot } from './support/environment.mjs';

export default function prepareBrowserDatabase() {
    const database = assertDisposableDatabase();
    mkdirSync(path.dirname(database), { recursive: true });
    closeSync(openSync(database, 'a'));

    const result = spawnSync(phpBinary(), [
        'artisan',
        'migrate:fresh',
        '--seeder=Database\\Seeders\\AcceptanceDemoSeeder',
        '--force',
        '--no-interaction',
    ], {
        cwd: repositoryRoot,
        env: applicationEnvironment(),
        encoding: 'utf8',
        stdio: 'pipe',
        windowsHide: true,
    });

    if (result.status !== 0) {
        throw new Error(`Could not prepare isolated browser database.\n${result.error ?? ''}\n${result.stdout}\n${result.stderr}`);
    }

    process.stdout.write(result.stdout);

    const fixtureResult = spawnSync(phpBinary(), [
        path.join('tests', 'Browser', 'support', 'prepare-database.php'),
    ], {
        cwd: repositoryRoot,
        env: applicationEnvironment(),
        encoding: 'utf8',
        stdio: 'pipe',
        windowsHide: true,
    });

    if (fixtureResult.status !== 0) {
        throw new Error(`Could not prepare browser-only fixtures.\n${fixtureResult.error ?? ''}\n${fixtureResult.stdout}\n${fixtureResult.stderr}`);
    }
}
