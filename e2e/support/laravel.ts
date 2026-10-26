
import { execFileSync } from 'node:child_process';
import {
  existsSync,
  mkdirSync,
  readFileSync,
  statSync,
  writeFileSync,
} from 'node:fs';
import { dirname, resolve } from 'node:path';

const root = process.cwd();

export const LOG_FILE = resolve(root, 'storage/logs/laravel.log');

export function artisan(...args: string[]): string {
  return execFileSync('php', ['artisan', ...args], {
    cwd: root,
    env: process.env,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

/**
 * Reset only the isolated E2E database.
 * Refuses to proceed unless .env.testing explicitly targets sldwp_e2e.
 */
export function resetDatabase(): void {
  const testingEnvPath = resolve(root, '.env.testing');

  if (!existsSync(testingEnvPath)) {
    throw new Error('Safety check failed: .env.testing was not found.');
  }

  const testingEnv = readFileSync(testingEnvPath, 'utf8');
  const databaseMatch = testingEnv.match(/^DB_DATABASE\s*=\s*(.*?)\s*$/m);
  const connectionMatch = testingEnv.match(/^DB_CONNECTION\s*=\s*(.*?)\s*$/m);

  const database = databaseMatch?.[1]?.replace(/^['"]|['"]$/g, '');
  const connection = connectionMatch?.[1]?.replace(/^['"]|['"]$/g, '');

  if (database !== 'sldwp_e2e') {
    throw new Error(
      `Safety check failed: expected DB_DATABASE=sldwp_e2e in .env.testing; found ${database ?? '(missing)'}.`,
    );
  }

  if (connection !== 'mysql' && connection !== 'sqlite') {
    throw new Error(
      `Safety check failed: unsupported E2E DB_CONNECTION=${connection ?? '(missing)'}.`,
    );
  }

  if (connection === 'sqlite') {
    const file = resolve(root, database);

    mkdirSync(dirname(file), { recursive: true });

    if (!existsSync(file)) {
      writeFileSync(file, '');
    }
  }

  // Explicitly load Laravel's testing environment.
  artisan(
    'migrate:fresh',
    '--seed',
    '--force',
    '--no-interaction',
    '--env=testing',
  );

  if (!existsSync(LOG_FILE)) {
    mkdirSync(dirname(LOG_FILE), { recursive: true });
    writeFileSync(LOG_FILE, '');
  }
}

/** Byte offset of the log; readMfaCode ignores older messages. */
export function logMark(): number {
  return existsSync(LOG_FILE) ? statSync(LOG_FILE).size : 0;
}

/**
 * Read the newest six-digit MFA code written after the given log offset.
 */
export function readMfaCode(mark: number): string | null {
  if (!existsSync(LOG_FILE)) return null;

  const text = readFileSync(LOG_FILE)
    .subarray(mark)
    .toString('utf8')
    .replace(/=\r?\n/g, '');

  const patterns = [
    /code is:\s*(?:\*\*|<strong>)?\s*(\d{6})(?:\*\*|<\/strong>)?/gi,
    /SL-DWP code:\s*(\d{6})/gi,
  ];

  const hits: string[] = [];

  for (const pattern of patterns) {
    for (const match of text.matchAll(pattern)) {
      hits.push(match[1]);
    }
  }

  return hits.length ? hits[hits.length - 1] : null;
}
