#!/bin/bash
####
# Prepares environment and runs the unit tests.
# @See <project root>/Makefile
####
# @since 2018-09-23
# @author stev leibelt <artodeto.bazzline.net>
####

#begin of dependency
BE_VERBOSE=0
CURRENT_WORKING_DIRECTORY=$(pwd)
IS_DEBUG=0
PATH_TO_THIS_CURRENT_SCRIPT_BASH=$(cd $(dirname "${BASH_SOURCE[0]}"); pwd)
PRINT_HELP=0
#end of dependency

#begin of user input
while true;
do
    case "${1}" in
        -d)
            IS_DEBUG=1
            shift
            ;;
        -h)
            PRINT_HELP=1
            shift
            ;;
        -v)
            BE_VERBOSE=1
            shift
            ;;
        *)
            break
            ;;
    esac
done
#end of user input

#begin of business logic
if [[ ${PRINT_HELP} -eq 1 ]];
then
    echo ":: Usage"
    echo "   "$(basename ${0})" [-d] [-h] [-v]"
    echo ""
    echo "  -d  - Enable debug."
    echo "  -h  - Print this help."
    echo "  -v  - Enable verbosity."
    cd "${CURRENT_WORKING_DIRECTORY}"
    exit 0
fi

#change to directory root
cd "${PATH_TO_THIS_CURRENT_SCRIPT_BASH}/.."

if [[ ${BE_VERBOSE} -gt 0 ]];
then
    echo ":: Current working directory is."
    echo "   "$(pwd)
fi

#prepare environment
if [[ ${BE_VERBOSE} -gt 0 ]];
then
    echo ":: Preparing environment."
    echo "   Removing >>tests/Fixtures/*/build<<."
    echo "   Removing >>tests/Fixtures/fixtures_built<<."
fi
rm -rf tests/Fixtures/*/build
rm -f tests/Fixtures/fixtures_built


if [[ ${IS_DEBUG} -eq 0 ]];
then
    if [[ ${BE_VERBOSE} -gt 0 ]];
    then
        echo ":: Starting phpunit"
    fi

    ./vendor/bin/phpunit
else
    if [[ ${BE_VERBOSE} -gt 0 ]];
    then
        echo ":: Starting phpunit --stop-on-failure"
    fi

    ./vendor/bin/phpunit --stop-on-failure
fi

cd "${CURRENT_WORKING_DIRECTORY}"
#begin of business logic
