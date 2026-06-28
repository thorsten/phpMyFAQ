/**
 * JSON envelope returned by the update/health-check endpoints (`api/upgrade.ts`).
 *
 * Distinct from `ApiResponse`: alongside `success`/`error`/`message` these
 * endpoints report an update `version`, when it was `dateLastChecked`, and a
 * non-fatal `warning`. Use this as the type argument to `fetchJson`.
 */
export interface ResponseData {
  success?: string;
  warning?: string;
  error?: string;
  dateLastChecked?: string;
  version?: string;
  message?: string;
}
