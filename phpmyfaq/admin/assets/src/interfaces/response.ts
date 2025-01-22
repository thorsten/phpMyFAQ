export interface Response {
  json(): void | PromiseLike<void>;
  success: boolean;
  message?: string;
  error?: string;
  status?: string;
}
