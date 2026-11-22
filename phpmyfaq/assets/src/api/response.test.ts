import { describe, expect, test } from 'vitest';
import { parseJsonResponse } from './response';

const jsonResponse = (status: number, body: unknown): Response =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

describe('parseJsonResponse', () => {
  test('returns the decoded body of a successful response', async () => {
    await expect(parseJsonResponse(jsonResponse(200, { success: 'Saved' }))).resolves.toEqual({ success: 'Saved' });
  });

  test('throws the translated server message of a refused request', async () => {
    const response = jsonResponse(400, { error: 'Please enter the captcha code' });

    await expect(parseJsonResponse(response)).rejects.toThrow('Please enter the captcha code');
  });

  test('falls back to the HTTP status when the body carries no error message', async () => {
    await expect(parseJsonResponse(jsonResponse(500, { message: 'boom' }))).rejects.toThrow('HTTP 500');
    await expect(parseJsonResponse(jsonResponse(400, { error: '' }))).rejects.toThrow('HTTP 400');
    await expect(parseJsonResponse(jsonResponse(400, { error: ['a', 'b'] }))).rejects.toThrow('HTTP 400');
    await expect(parseJsonResponse(jsonResponse(400, null))).rejects.toThrow('HTTP 400');
  });

  test('falls back to the HTTP status when the body is not JSON', async () => {
    const response = new Response('<html>Bad Gateway</html>', { status: 502 });

    await expect(parseJsonResponse(response)).rejects.toThrow('HTTP 502');
  });
});
