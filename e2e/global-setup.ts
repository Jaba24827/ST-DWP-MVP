import { resetDatabase } from './support/laravel';

export default async function globalSetup() {
  if (process.env.E2E_BASE_URL) {
    console.log('E2E_BASE_URL is set; skipping database reset for the external server.');
    return;
  }

  console.log('Resetting and seeding the isolated E2E test database...');
  await resetDatabase();
  console.log('E2E database reset and seeding completed.');
}