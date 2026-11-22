export interface OAuth2ClientPayload {
  name: string;
  redirectUri: string;
  grants: string[];
  confidential: boolean;
}

export interface OAuth2ClientResponse {
  clientId?: string;
  // Only part of the response that registers a confidential client
  clientSecret?: string | null;
  success?: string;
  error?: string;
}

export interface OAuth2Scope {
  scope: string;
  description: string;
}
