#!/bin/sh

# usage: ./run_inspect.sh /path/to/phpstorm/root /path/to/piwik/root PluginName

PHPSTORM_PATH="$1"
PIWIK_PATH="$2"
PLUGIN_NAME="$3"

SCRIPT_DIR=`dirname $0`
INSPECTION_PROFILE_PATH="$SCRIPT_DIR/Plugin_Quality_Checks.xml"
OUTPUT_PATH="$SCRIPT_DIR/output"

mkdir -p "$OUTPUT_PATH"

echo "Running command:"

CMD="\"$PHPSTORM_PATH/bin/inspect.sh\" \"$PIWIK_PATH\" \"$INSPECTION_PROFILE_PATH\" \"$OUTPUT_PATH\" -d \"$PIWIK_PATH/plugins/$PLUGIN_NAME\" -v2"

$CMD