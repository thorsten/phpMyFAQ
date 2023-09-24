<?php

namespace phpMyFAQ\Enums;

enum AuthenticationSourceType: string
{
    case AUTH_LOCAL = 'local';
    case AUTH_AZURE = 'azure';
    case AUTH_LDAP = 'ldap';
    case AUTH_HTTP = 'http';
    case AUTH_SSO = 'sso';
}
