import { spawn } from 'node:child_process';
import { applicationEnvironment, phpBinary, repositoryRoot } from './support/environment.mjs';
import prepareBrowserDatabase from './global-setup.mjs';

prepareBrowserDatabase();

const server = spawn(phpBinary(), [
    'artisan',
    'serve',
    '--host=127.0.0.1',
    '--port=4173',
    '--no-reload',
], {
    cwd: repositoryRoot,
    env: applicationEnvironment(),
    stdio: 'inherit',
    windowsHide: true,
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}

server.on('exit', (code) => process.exit(code ?? 0));
