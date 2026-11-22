export interface ApiKey {
  id: number | string;
  name: string;
  scopes: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created: string;
}

export interface ApiKeyPayload {
  name: string;
  scopes: string[];
  // 'YYYY-MM-DD HH:MM:SS', or an empty string for a key that does not expire
  expiresAt: string;
}

export interface ApiKeyResponse {
  id?: number;
  // Only part of the response that creates the key
  apiKey?: string;
  success?: boolean;
  error?: string;
}
