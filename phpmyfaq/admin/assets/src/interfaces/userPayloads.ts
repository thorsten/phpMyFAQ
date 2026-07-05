/**
 * Request body for PUT ./api/user/edit.
 */
export interface UserEditPayload {
  csrfToken: string;
  userId: string;
  display_name: string;
  email: string;
  last_modified: string;
  user_status: string;
  is_superadmin: boolean;
  overwrite_twofactor: boolean;
}

/**
 * Request body for POST ./api/user/add.
 */
export interface AddUserPayload {
  csrf: string;
  userName: string;
  realName: string;
  email: string;
  automaticPassword: boolean;
  password: string;
  passwordConfirm: string;
  isSuperAdmin: boolean;
}
