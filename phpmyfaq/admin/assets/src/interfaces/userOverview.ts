/**
 * One user as returned by GET ./api/user/users without a filter.
 */
export interface UserOverview {
  id: number;
  status: string;
  isSuperAdmin: boolean;
  isVisible: string | number;
  displayName: string;
  userName: string;
  email: string;
  authSource: string;
}
