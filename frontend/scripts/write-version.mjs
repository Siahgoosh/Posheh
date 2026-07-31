#!/usr/bin/env node
/** Writes build stamp into dist/ for cache-bust verification on server. */
import { writeFileSync, mkdirSync } from 'fs'
import { execSync } from 'child_process'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const dist = path.resolve(__dirname, '../dist')
mkdirSync(dist, { recursive: true })

let gitSha = 'unknown'
try {
  gitSha = execSync('git rev-parse --short HEAD', { encoding: 'utf8' }).trim()
} catch {
  // ignore
}

const payload = {
  build: new Date().toISOString(),
  git: gitSha,
  auth: 'password-v2',
  ui: 'email-login',
}

writeFileSync(path.join(dist, 'version.json'), JSON.stringify(payload, null, 2))
console.log('Wrote dist/version.json', payload)
