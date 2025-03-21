export interface UserData {
  userId: string;
  login: string;
  displayName: string;
  email: string;
  status: string;
  lastModified: string;
  authSource: string;
  twoFactorEnabled: string;
  isSuperadmin: string;
  json(): Promise<UserData>;
}
