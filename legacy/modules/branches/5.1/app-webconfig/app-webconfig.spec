#------------------------------------------------------------------------------
# P A C K A G E  I N F O
#------------------------------------------------------------------------------

Name: app-webconfig
Version: 5.1
Release: 30
Summary: Web-based administration tool - core modules
License: GPL
Group: Applications/Modules
Source: %{name}-%{version}.tar.gz
Vendor: Point Clark Networks
Packager: Point Clark Networks
# Most of these are derived from the required sudo entries defined below
Requires: chkconfig
Requires: coreutils
Requires: ethtool
Requires: file
Requires: initscripts
Requires: net-tools
Requires: sudo
Requires: SysVinit
Requires: util-linux
Requires: webconfig-php
Requires: webconfig-utils
Requires: app-setup = 5.1
Requires: app-webconfig-default = 5.1
Provides: cc-webconfig
Obsoletes: cc-webconfig
BuildRoot: %_tmppath/%name-%version-buildroot

%description
Web-based administration tool - core modules

#------------------------------------------------------------------------------
# B U I L D
#------------------------------------------------------------------------------

%prep
%setup
%build

#------------------------------------------------------------------------------
# I N S T A L L  F I L E S
#------------------------------------------------------------------------------

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

mkdir -p -m 755 $RPM_BUILD_ROOT/var/webconfig/htdocs/tmp
mkdir -p -m 755 $RPM_BUILD_ROOT/var/webconfig/common
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/share/system/modules/webconfig
mkdir -p -m 755 $RPM_BUILD_ROOT/usr/bin

cp -r webconfig/* $RPM_BUILD_ROOT/var/webconfig
install -m 644 Config.inc.php $RPM_BUILD_ROOT/var/webconfig/common
install -m 755 upgrade $RPM_BUILD_ROOT/usr/share/system/modules/webconfig/
install -m 755 upgrade-api $RPM_BUILD_ROOT/usr/share/system/modules/webconfig/
install -m 755 api $RPM_BUILD_ROOT/usr/bin/api

#------------------------------------------------------------------------------
# P R E P  S C R I P T
#------------------------------------------------------------------------------

%pre
# Add the webconfig user
CHECKUSER=`grep ^webconfig /etc/passwd 2>/dev/null`
if [ -z "$CHECKUSER" ]; then
	logger -p local6.notice -t installer "app-webconfig - adding webconfig user"
	adduser -d /var/webconfig -s /bin/false -c Webconfig -M webconfig -r
fi

#------------------------------------------------------------------------------
# I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%post
logger -p local6.notice -t installer "app-webconfig - installing"

if ( [ $1 == 1 ] && [ ! -e /etc/system/pre5x ] ); then
	logger -p local6.notice -t installer "app-webconfig - enabling on boot"
	/sbin/chkconfig --add webconfig
fi

/usr/sbin/addsudo /bin/cat app-webconfig
/usr/sbin/addsudo /bin/chmod app-webconfig
/usr/sbin/addsudo /bin/chown app-webconfig
/usr/sbin/addsudo /bin/cp app-webconfig
/usr/sbin/addsudo /bin/kill app-webconfig
/usr/sbin/addsudo /bin/ls app-webconfig
/usr/sbin/addsudo /bin/mkdir app-webconfig
/usr/sbin/addsudo /bin/mv app-webconfig
/usr/sbin/addsudo /bin/rm app-webconfig
/usr/sbin/addsudo /bin/touch app-webconfig
/usr/sbin/addsudo /sbin/chkconfig app-webconfig
/usr/sbin/addsudo /sbin/shutdown app-webconfig
/usr/sbin/addsudo /sbin/service app-webconfig
/usr/sbin/addsudo /usr/bin/api app-webconfig
/usr/sbin/addsudo /usr/bin/file app-webconfig
/usr/sbin/addsudo /usr/bin/find app-webconfig
/usr/sbin/addsudo /usr/bin/head app-webconfig
/usr/sbin/addsudo /usr/bin/chfn app-webconfig
/usr/sbin/addsudo /usr/bin/du app-webconfig
/usr/sbin/addsudo /usr/sbin/app-passwd app-webconfig
/usr/sbin/addsudo /usr/sbin/app-realpath app-webconfig
/usr/sbin/addsudo /usr/sbin/app-rename app-webconfig
/usr/sbin/addsudo /usr/sbin/userdel app-webconfig

if [ -e /etc/rc3.d/S99webconfig ]; then
	/sbin/chkconfig --del webconfig
	/sbin/chkconfig --add webconfig
fi

/usr/share/system/modules/webconfig/upgrade >/dev/null 2>&1

exit 0

#------------------------------------------------------------------------------
# U N I N S T A L L  S C R I P T
#------------------------------------------------------------------------------

%preun
if [ "$1" = "0" ]; then
	logger -p local6.notice -t installer "app-webconfig - uninstalling"
	/sbin/service webconfig stop >/dev/null 2>&1
	/sbin/chkconfig --del webconfig
fi

#------------------------------------------------------------------------------
# C L E A N  U P
#------------------------------------------------------------------------------

%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

#------------------------------------------------------------------------------
# F I L E S
#------------------------------------------------------------------------------

%files
%defattr(-,root,root)
%dir %attr(-,webconfig,webconfig) /var/webconfig/htdocs/tmp
%dir /usr/share/system/modules/webconfig
/usr/share/system/modules/webconfig/upgrade
/usr/share/system/modules/webconfig/upgrade-api
/usr/bin/api
/var/webconfig
%config(noreplace) /var/webconfig/common/Config.inc.php
