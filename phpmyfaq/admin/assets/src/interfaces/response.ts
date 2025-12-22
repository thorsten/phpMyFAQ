export interface Response {
  json(): void | PromiseLike<void>;
  success: string;
  message?: string;
  error?: string;
  status?: string;
  data?: string;
}

export interface GlossaryResponse {
  item: string;
  definition: string;
}
