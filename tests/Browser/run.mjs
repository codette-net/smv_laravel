import { spawn } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';
import { defaultDatabase, repositoryRoot } from './support/environment.mjs';

const arguments_ = process.argv.slice(2);
const staging = arguments_[0] === '--staging';

if (staging) {
    arguments_.shift();

    if (! process.env.SMV_STAGING_BASE_URL) {
        throw new Error('Set SMV_STAGING_BASE_URL before running the read-only staging smoke.');
    }
}

const environment = {
    ...process.env,
    SMV_BROWSER_MODE: staging ? 'staging' : 'local',
};

if (! staging) {
    environment.SMV_BROWSER_ALLOW_RESET = '1';
    environment.SMV_BROWSER_DATABASE = process.env.SMV_BROWSER_DATABASE ?? defaultDatabase;
}

const command = path.join(repositoryRoot, 'node_modules', 'playwright', 'cli.js');
const runner = spawn(process.execPath, [command, 'test', ...arguments_], {
    cwd: repositoryRoot,
    env: environment,
    stdio: 'inherit',
    windowsHide: true,
});

runner.on('exit', (code) => process.exit(code ?? 1));
