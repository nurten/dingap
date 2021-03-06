# Id: $
vendor := clearfoundation
product := enterprise
version := 5.1
release := 30
cwd := $(shell pwd)
module := $(notdir $(shell pwd))

all: ${module}.spec ${module}-${version}.tar.gz

${module}.spec: ${module}.spec.in
	@if [ -x "./configure" ]; then \
		./configure ${vendor} ${product} ${version} ${release}; \
	fi
	@echo -en "\\033[1;32m"
	@echo "Creating ${module}.spec..."
	@echo -en "\\033[0;39m"
	@../BUILD/subst ../BUILD/.config ${module}.spec.in ${module}.spec

${module}-${version}.tar.gz: MANIFEST MANIFEST.${product}
	@echo -en "\\033[1;32m"
	@echo "Creating ${module}-${version}.tar.gz..."
	@echo -en "\\033[0;39m"
	@(../BUILD/mktgz ~/rpms/SOURCES/${module}-${version}) || exit 1

MANIFEST.${product}:
	@if [ ! -f "MANIFEST.${product}" ]; then \
		touch MANIFEST.${product} ;\
	fi

tgz: ${module}.spec
	@echo -en "\\033[1;32m"
	@echo "Creating ${module}-${version}.tar.gz..."
	@echo -en "\\033[0;39m"
	@(cd .. && ln -sf ${module} ${module}-${version})
	@(cd .. && tar -cvhzf ~/rpms/SOURCES/${module}-${version}.tar.gz ${module}-${version})
	@(cd .. && rm -f ${module}-${version})

# vi: ts=4
