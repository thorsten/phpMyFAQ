/**
 * Shape of a single tag returned by the tag autocomplete API endpoint.
 *
 * The `./api/content/tags` endpoint answers with a list of these objects. Use it
 * as the type argument to `fetchJson`, e.g. `fetchJson<TagResponse[]>(url, options)`,
 * instead of casting the result.
 */
export interface TagResponse {
  id: string;
  name: string;
}
