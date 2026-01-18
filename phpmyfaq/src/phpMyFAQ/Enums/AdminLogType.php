<?php

/**
 * Admin log type enum
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Enums;

enum AdminLogType: string
{
    // Backup operations
    case BACKUP_EXPORT = 'backup-export';
    case BACKUP_RESTORE = 'backup-restore';

    // FAQ operations
    case FAQ_ADD = 'faq-add';
    case FAQ_EDIT = 'faq-edit';
    case FAQ_COPY = 'faq-copy';
    case FAQ_TRANSLATE = 'faq-translate';
    case FAQ_ANSWER_ADD = 'faq-answer-add';
    case FAQ_DELETE = 'faq-delete';
    case FAQ_PUBLISH = 'faq-publish';

    // Category operations
    case CATEGORY_ADD = 'category-add';
    case CATEGORY_EDIT = 'category-edit';
    case CATEGORY_DELETE = 'category-delete';
    case CATEGORY_REORDER = 'category-reorder';
    case CATEGORY_TRANSLATE = 'category-translate';

    // Comments
    case COMMENT_DELETE = 'comment-delete';

    // Attachments
    case ATTACHMENT_ADD = 'attachment-add';
    case ATTACHMENT_DELETE = 'attachment-delete';

    // News
    case NEWS_ADD = 'news-add';
    case NEWS_EDIT = 'news-edit';
    case NEWS_DELETE = 'news-delete';
    case NEWS_TRANSLATE = 'news-translate';

    // Custom Pages
    case PAGE_ADD = 'page-add';
    case PAGE_EDIT = 'page-edit';
    case PAGE_DELETE = 'page-delete';
    case PAGE_TRANSLATE = 'page-translate';

    // Configuration
    case CONFIG_CHANGE = 'config-change';
    case CONFIG_SECURITY_CHANGED = 'config-security-changed';
    case CONFIG_LDAP_CHANGED = 'config-ldap-changed';
    case CONFIG_SSO_CHANGED = 'config-sso-changed';
    case CONFIG_ENCRYPTION_CHANGED = 'config-encryption-changed';

    // User management
    case USER_ADD = 'user-add';
    case USER_EDIT = 'user-edit';
    case USER_DELETE = 'user-delete';
    case USER_CHANGE_PASSWORD = 'user-change-password';
    case USER_CHANGE_PERMISSIONS = 'user-change-permissions';
    case USER_PASSWORD_RESET_REQUESTED = 'user-password-reset-requested';
    case USER_PASSWORD_RESET_COMPLETED = 'user-password-reset-completed';
    case USER_STATUS_CHANGED = 'user-status-changed';
    case USER_SUPERADMIN_GRANTED = 'user-superadmin-granted';
    case USER_SUPERADMIN_REVOKED = 'user-superadmin-revoked';

    // Group management
    case GROUP_ADD = 'group-add';
    case GROUP_EDIT = 'group-edit';
    case GROUP_DELETE = 'group-delete';
    case GROUP_CHANGE_PERMISSIONS = 'group-change-permissions';

    // Authentication & Authorization
    case AUTH_LOGIN_SUCCESS = 'auth-login-success';
    case AUTH_LOGIN_FAILED = 'auth-login-failed';
    case AUTH_LOGOUT = 'auth-logout';
    case AUTH_SESSION_TIMEOUT = 'auth-session-timeout';
    case AUTH_SESSION_TERMINATED = 'auth-session-terminated';

    // Two-Factor Authentication
    case AUTH_2FA_ENABLED = 'auth-2fa-enabled';
    case AUTH_2FA_DISABLED = 'auth-2fa-disabled';
    case AUTH_2FA_SUCCESS = 'auth-2fa-success';
    case AUTH_2FA_FAILED = 'auth-2fa-failed';
    case AUTH_2FA_RESET = 'auth-2fa-reset';

    // WebAuthn
    case AUTH_WEBAUTHN_REGISTER = 'auth-webauthn-register';
    case AUTH_WEBAUTHN_LOGIN_SUCCESS = 'auth-webauthn-login-success';
    case AUTH_WEBAUTHN_LOGIN_FAILED = 'auth-webauthn-login-failed';
    case AUTH_WEBAUTHN_REMOVED = 'auth-webauthn-removed';

    // Security Events
    case SECURITY_UNAUTHORIZED_ACCESS = 'security-unauthorized-access';
    case SECURITY_CSRF_VIOLATION = 'security-csrf-violation';
    case SECURITY_PERMISSION_VIOLATION = 'security-permission-violation';
    case SECURITY_SUSPICIOUS_ACTIVITY = 'security-suspicious-activity';
    case SECURITY_RATE_LIMIT_EXCEEDED = 'security-rate-limit-exceeded';

    // Data Exports
    case DATA_EXPORT_USERS = 'data-export-users';
    case DATA_EXPORT_SESSIONS = 'data-export-sessions';
    case DATA_EXPORT_FAQS = 'data-export-faqs';
    case DATA_EXPORT_LOGS = 'data-export-logs';

    // API Security
    case API_KEY_CREATED = 'api-key-created';
    case API_KEY_REVOKED = 'api-key-revoked';
    case API_UNAUTHORIZED_ACCESS = 'api-unauthorized-access';

    // System Security
    case SYSTEM_MAINTENANCE_MODE_ENABLED = 'system-maintenance-mode-enabled';
    case SYSTEM_MAINTENANCE_MODE_DISABLED = 'system-maintenance-mode-disabled';
    case SYSTEM_UPDATE_STARTED = 'system-update-started';
    case SYSTEM_UPDATE_COMPLETED = 'system-update-completed';
    case SYSTEM_UPDATE_FAILED = 'system-update-failed';
}
