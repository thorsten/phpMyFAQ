#
# This is the spec file for building an RPM package of phpMyFAQ
# for most of the different RPM-based distributions
#
# How to build the RPM package.
# It depends on your rpm version:
# a. (OLD) rpm -ta path/to/phpmyfaq-<VERSION>.full.tar.gz
# b. (NEW) rpmbuild -ta path/to/phpmyfaq-<VERSION>.full.tar.gz
#
# Where do you find the just builded RPM packages?
# Red Hat:
# - SRPM: /usr/src/redhat/SRPMS/phpmyfaq-<VERSION>-<PACKAGE RELEASE>.src.rpm
# - RPM: /usr/src/redhat/RPMS/noarch/phpmyfaq-<VERSION>-<PACKAGE RELEASE>.noarch.rpm
# SUSE:
# - SRPM: /usr/src/packages/SRPMS/phpmyfaq-<VERSION>-<PACKAGE RELEASE>.src.rpm
# - RPM: /usr/src/packages/RPMS/noarch/phpmyfaq-<VERSION>-<PACKAGE RELEASE>.noarch.rpm
#
# How this spec file is expected to work.
# INSTALL.
# 1. Install phpMyFAQ into /var/www/phpmyfaq-<version>-<release>.
# 2. Make a symbolic link for the current install folder to '/var/www/phpmyfaq'.
#    The use of a symbolic link will give the user an easy way
#    of recovering the old install and/or preserve old versions
#    w/o changing the URL mapping.
# 3. Return telling the user the URL for the web install stage
#    and other important notes about the steps that the user must manually perform.
# UPDATE.
# 1. Copy the current code to the folder in which phpMyFAQ will be updated
# 2. Preserve the new 'template' folder (rename it into 'template-<version>-<release>.orig')
#    and put in production the previous 'template' folder (maybe the user customized it)
# 3. Return telling the user the URL for the web update stage
#    and other important notes about the steps that the user must manually perform.
# REMOVE.
# 1. Remove any filename equal to those packed into the tar.gz source
# 2. Backup the 'template' folder (rename it into 'template-<version>-<release>.custom')
#
# KNOWN ISSUES
# 1. Relocation (rpm flag: --relocate OLDPATH=NEWPATH) is not working as expected
#
# @author    Matteo Scaramuccia <matteo@scaramuccia.com>
# @since     2006-07-05
# @copyright 2006-2011 phpMyFAQ Team
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.

#
# phpMyFAQ
#
%define name        phpmyfaq
%define version     2.8.0
%define release     1
%define epoch       0

%define httpd_name      httpd
# User and Group under which Apache is running
# Red Hat: apache:apache
%define httpd_user      apache
%define httpd_group     apache
# OpenSUSE: wwwrun:www
%if "%{_vendor}" == "suse"
    %define httpd_name  apache2
    %define httpd_user  wwwrun
    %define httpd_group www
%endif

# Red Hat
# Apache server is packaged under the name of:
# - apache: up to Red Hat 7.3 and Red Hat Enterprise 2.1
# - httpd: after these releases above
%define is_rh7      0
%define is_el2      0
%define is_centos2  0
%if %(test -f "/etc/redhat-release" && echo 1 || echo 0)
    %define is_rh7 %(test -n "`cat /etc/redhat-release | grep '(Valhalla)'`" && echo 1 || echo 0)
    %define is_el2 %(test -n "`cat /etc/redhat-release | grep '(Pensacola)'`" && echo 1 || echo 0)
    %define is_centos2 %(test -n "`cat /etc/redhat-release | grep 'CentOS release 2'`" && echo 1 || echo 0)
%endif
%define is_apache   0
%if %{is_rh7}
%define is_apache   1
%endif
%if %{is_el2}
%define is_apache   1
%endif
%if %{is_centos2}
%define is_apache   1
%endif
# Evaluate PHP version
%define phpver_lt_520 %(out=`rpm -q --queryformat='%{VERSION}' php` 2>&1 >/dev/null || out=0 ; out=`echo $out | tr . : | sed s/://g` ; if [ $out -lt 520 ] ; then out=1 ; else out=0; fi ; echo $out)

Summary:            phpMyFAQ is an open source FAQ system
Name:               %{name}
Version:            %{version}
Release:            %{release}
Epoch:              %{epoch}
License:            MPL
Vendor:             phpMyFAQ
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.phpmyfaq.de
Group:              Networking/WWW
Packager:           Matteo Scaramuccia <matteo@scaramuccia.com>, Thorsten Rinne <thorsten@phpmyfaq.de>

%if "%{_vendor}" == "suse"
Prefix:             /srv/www
%else
Prefix:             /var/www
%endif
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArchitectures: noarch

AutoReq:            0
%if "%{_vendor}" == "suse"
Requires:           apache2
Requires:           apache2-mod_php5
Requires:           php5-gd
# We do not require MySQL but one among the several DB supported by phpMyFAQ.
# Here we make the strong assumption that an RPM will mostly be installed on a LAMP server.
Requires:           mysql, php5-mysql
%else
%if %{is_apache}
Requires:           apache
%else
Requires:           httpd
%endif
Requires:           php >= 5.2.3
# GD is bundled into PHP starting from PHP 4.3.0
Requires:           php-gd
# We do not require MySQL but one among the several DB supported by phpMyFAQ.
# Here we make the strong assumption that an RPM will mostly be installed on a LAMP server.
Requires:           mysql, mysql-server, php-mysql
%endif

Provides:           %{name}-%{version}

%description
phpMyFAQ is a multilingual, completely database-driven FAQ-system.
It supports various databases to store all data, PHP 5.2.0 (or higher)
is needed in order to access this data.
phpMyFAQ also offers a Content Management-System with a WYSIWYG editor
and an Image Manager, flexible multi-user support with LDAP support,
a news-system, user-tracking, language modules, enhanced automatic
content negotiation, accessible XHTML based templates, extensive
XML-support, PDF-support, a backup-system and an easy to use
installation script.

%changelog
==========

* Wed Sep 28 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.alpha-1
- New upstream version 2.8.0-alpha

* Fri Sep 30 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.1-1
- New upstream version 2.7.1

* Tue Sep 20 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0-1
- New upstream version 2.7.0

* Sun Aug 17 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.RC-1
- New upstream version 2.7.0-RC

* Sun Aug 07 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.beta3-1
- New upstream version 2.7.0-beta3

* Thu Mar 24 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.beta-1
- New upstream version 2.7.0-beta

* Sat Jan 28 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.alpha2-1
- New upstream version 2.7.0-alpha2

* Mon Aug 08 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.7.0.alpha-1
- New upstream version 2.7.0-alpha

* Wed Jun 22 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.18-1
- New upstream version 2.6.18

* Tue May 31 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.17-1
- New upstream version 2.6.17

* Tue Mar 01 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.16-1
- New upstream version 2.6.16

* Sat Jan 28 2011 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.15-1
- New upstream version 2.6.15

* Sat Dec 18 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.14-1
- New upstream version 2.6.14

* Wed Dec 13 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.13-1
- New upstream version 2.6.13

* Wed Nov 03 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.12-1
- New upstream version 2.6.12

* Tue Nov 02 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.11-1
- New upstream version 2.6.11

* Tue Sep 28 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.10-1
- New upstream version 2.6.10

* Tue Aug 31 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.9-1
- New upstream version 2.6.9

* Wed Jul 29 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.8-1
- New upstream version 2.6.8

* Mon Jun 21 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.7-1
- New upstream version 2.6.7

* Mon May 24 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.6-1
- New upstream version 2.6.6

* Sun Apr 18 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.5-1
- New upstream version 2.6.5

* Mon Apr 4 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.4-1
- New upstream version 2.6.4

* Mon Feb 1 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.3-1
- New upstream version 2.6.3

* Sun Jan 24 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.2-1
- New upstream version 2.6.2

* Mon Jan 11 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.1-1
- New upstream version 2.6.1

* Sun Dec 08 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.0-1
- New upstream version 2.6.0

* Sun Nov 22 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.0.RC-1
- New upstream version 2.6.0-RC

* Mon Nov 07 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.6.0.beta-1
- New upstream version 2.6.0-beta

* Tue Jan 19 2010 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.8-1
- New upstream version 2.5.8.

* Tue Dec 22 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.7-1
- New upstream version 2.5.7.

* Tue Dec 01 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.6-1
- New upstream version 2.5.6.

* Tue Nov 10 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.5-1
- New upstream version 2.5.5.

* Mon Oct 19 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.4-1
- New upstream version 2.5.4.

* Tue Sep 01 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.3-1
- New upstream version 2.5.3.

* Mon Aug 10 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.2-1
- New upstream version 2.5.2.

* Tue Jul 21 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.1-1
- New upstream version 2.5.1.

* Sat Jul 11 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0-1
- New upstream version 2.5.0.

* Mon Jun 15 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0.RC3-1
- New upstream version 2.5.0-RC3.

* Fri Jun 12 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0.RC2-1
- New upstream version 2.5.0-RC2.

* Mon May 04 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0.RC-1
- New upstream version 2.5.0-RC.

* Mon Mar 09 2009 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0.beta-1
- New upstream version 2.5.0-beta.

* Sun May 18 2008 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.5.0.alpha-1
- New upstream version 2.5.0-alpha.

* Wed Feb 24 2008 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.7-1
- New upstream version 2.0.7.

* Wed Jan 20 2008 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.6-1
- New upstream version 2.0.6.

* Wed Nov 22 2007 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.5-1
- New upstream version 2.0.5.

* Sat Aug 22 2007 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.4-1
- New upstream version 2.0.4.

* Sat Jun 01 2007 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.2-1
- New upstream version 2.0.2.

* Sat May 20 2007 Thorsten Rinne <thorsten@phpmyfaq.de> - 2.0.1-1
- New upstream version 2.0.1.

* Sat May 12 2007 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0-1
- New upstream version 2.0.0.

* Sun Apr 29 2007 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.rc2-1
- Fix uncorrect source filename.
- New upstream version 2.0.0-RC2.

* Thu Apr 26 2007 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.rc-1
- New upstream version 2.0.0-RC.

* Sun Feb 25 2007 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.betaN-1
- Updated the PHP version requirement to be '4.3.3'.

* Tue Oct 10 2006 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.betaN-1
- New upstream version 2.0.0.beta.

* Sun Jul 16 2006 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.alphaN-4
- Fix some minor warnings during the RPM build under OpenSUSE.
- Fix Apache paths under OpenSUSE.
- Fix phpmfaq.conf to better fit with different Apache configurations.

* Tue Jul 11 2006 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0.alphaN-3
- More beta support for SUSE.

* Sun Jul 09 2006 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0-2
- Move the deployment folder from '/var/www/html' to '/var/www'
- Add phpmyfaq.conf, the Apache configuration file for phpMyFAQ
- Add beta support for SUSE.

* Sat Jul 08 2006 Matteo Scaramuccia <matteo@scaramuccia.com> - 2.0.0-1
- First spec release.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
# Remove the phpmyfaq.spec file: we do not need it
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%docdir %{prefix}/%{name}-%{version}-%{release}/docs
%{prefix}/%{name}-%{version}-%{release}

%pre
if [ $1 = 1 ] ; then
    # First phpMyFAQ install
    # Create an ad hoc phpmyfaq.conf
    # a. Red Hat: /etc/httpd/conf.d
    # b. OpenSUSE: /etc/apache2/conf.d
    /bin/cat << EOF > %{_sysconfdir}/%{httpd_name}/conf.d/%{name}.conf
# phpMyFaq - An open source FAQ system
Alias /phpmyfaq %{prefix}/%{name}
Alias /faq      %{prefix}/%{name}
# Sanity check on the Apache configuration
<Directory "%{prefix}">
    # Permit the use of symlinks
    Options +FollowSymLinks
</Directory>
# phpMyFAQ folder configuration
<Directory "%{prefix}/%{name}">
    # Permit the use of an .htaccess file and the use of all the Apache directives
    AllowOverride All
    # Users that can access to the FAQ server
    Order allow,deny
    Allow from all
</Directory>
EOF
fi
if [ $1 -gt 1 ] ; then
    # phpMyFAQ upgrade
    if [ -L "%{prefix}/%{name}" ] ; then
        # Remove any 'original' template folder
        rm -rf %{prefix}/%{name}/template-*.orig
        # Copy the current phpMyFAQ code for updating
        cp -aRf %{prefix}/%{name}/ %{prefix}/%{name}-%{version}-%{release}
        # Backup the template folder
        mv %{prefix}/%{name}-%{version}-%{release}/template  %{prefix}/%{name}-%{version}-%{release}/template.before-%{version}-%{release}
    fi
fi

%post
if [ -L "%{prefix}/%{name}" ] ; then
    rm %{prefix}/%{name} > /dev/null 2>&1
fi
ln -s %{name}-%{version}-%{release} %{prefix}/%{name}
echo
if [ $1 = 1 ] ; then
    # First phpMyFAQ install
    # Reload Apache for loading phpMyFAQ configuration
    %{_initrddir}/%{httpd_name} reload &> /dev/null
    # Prompt the user
    echo "phpMyFAQ is installed in: %{prefix}/%{name}-%{version}-%{release}"
    echo "Now you need to complete this installation launching"
    echo "the web interactive install stage:"
    echo
    echo "    http://$HOSTNAME/%{name}/install/setup.php"
    echo
    echo "You'll be asked for a MySQL database and its credentials"
    echo "and some other info."
    echo "Please first create a database in MySQL (or in one of the"
    echo "other supported DB) for phpMyFAQ."
else
    # phpMyFAQ upgrade
    # Put the previous template folder on-line.
    if [ -d "%{prefix}/%{name}/template.before-%{version}-%{release}" ] ; then
        mv %{prefix}/%{name}/template %{prefix}/%{name}/template-%{version}-%{release}.orig
        mv %{prefix}/%{name}/template.before-%{version}-%{release} %{prefix}/%{name}/template
    fi
    # Prompt the user
    echo "phpMyFAQ is upgraded in: %{prefix}/%{name}-%{version}-%{release}"
    echo "Now you need to complete this update launching"
    echo "the web interactive update stage:"
    echo
    echo "    http://$HOSTNAME/%{name}/install/update.php"
    echo
    echo "Please remember to read the 'docs/CHANGEDFILES.txt' to check"
    echo "for modifications into the template folder files."
    echo
    echo "WARNING."
    echo "It would be wise to make a backup of your phpMyFAQ DB"
    echo "before proceeding with the web interactive update stage."
fi
echo

%preun
if [ -d "%{prefix}/%{name}-%{version}-%{release}/template-%{version}-%{release}.orig" ] ; then
    rm -rf %{prefix}/%{name}-%{version}-%{release}/template-%{version}-%{release}.orig
fi
if [ $1 = 0 ] ; then
# Last phpMyFAQ uninstall
    if [ -f "%{_sysconfdir}/%{httpd_name}/conf.d/%{name}.conf" ] ; then
        # Remove phpMyFAQ Apache configuration file
        rm -f %{_sysconfdir}/%{httpd_name}/conf.d/%{name}.conf
        # Reload Apache for removing phpMyFAQ configuration
        %{_initrddir}/%{httpd_name} reload &> /dev/null
    fi
    mv %{prefix}/%{name}-%{version}-%{release}/template %{prefix}/%{name}-%{version}-%{release}/template-%{version}-%{release}.custom
    if [ -L "%{prefix}/%{name}" ] ; then
        rm %{prefix}/%{name} > /dev/null 2>&1
    fi
fi

%postun
if [ -L "%{prefix}/%{name}" ] ; then
    symlinked=`ls -l %{prefix}/%{name} | sed -r s/\(.\)+%{name}\ \-\>\ //`
    if [ "$symlinked" == "%{name}-%{version}-%{release}" ] ; then
        rm %{prefix}/%{name} > /dev/null 2>&1
    fi
fi
echo
echo "phpMyFAQ %{name}-%{version}-%{release} removed."
echo "Please remember that to complete the uninstall stage"
echo "you must manually remove the database. Besides you need"
echo "to check if the folder below exists:"
echo
echo "    %{prefix}/%{name}-%{version}-%{release}"
echo
echo "and manually remove:"
echo "1. the files (images and attachments) linked"
echo "   to the faq records;"
echo "2. the phpMyFAQ configuration(s) file(s)."
echo
