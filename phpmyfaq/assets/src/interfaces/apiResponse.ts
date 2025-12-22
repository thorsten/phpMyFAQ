export interface CommentData {
  date: string;
  username: string;
  email: string;
  gravatarUrl: string;
  comment: string;
}

export interface ApiResponse {
  success?: string;
  error?: string;
  result?: string;
  commentData?: CommentData;
}
