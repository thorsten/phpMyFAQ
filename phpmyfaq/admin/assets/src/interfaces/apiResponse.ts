/**
 * Shape of a JSON envelope returned by the admin API endpoints.
 *
 * Endpoints answer with either a `success` or an `error` message (and
 * occasionally an informational `message`), so a response carries at most one of
 * them. Some endpoints attach extra fields: a JSON `data` payload, a textual
 * `status`, or a `delete` flag. Use this as the type argument to `fetchJson`,
 * e.g. `fetchJson<ApiResponse>(url, options)`, instead of casting the result.
 *
 * This supersedes the older `Response` interface (see `response.ts`); remaining
 * uses of that interface should migrate here.
 */
export interface ApiResponse {
  success?: string;
  error?: string;
  message?: string;
  data?: string;
  status?: string;
  delete?: boolean;
}
