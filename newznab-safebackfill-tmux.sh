#!/bin/sh

SOURCE="${BASH_SOURCE[0]}"
DIR="$( dirname "$SOURCE" )"

while [ -h "$SOURCE" ]
do
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE"
  DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
done

DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

SESSIONNAME="newznab-safebackfill-tmux"
tmux has-session -t $SESSIONNAME &> /dev/null

if [ $? != 0 ]
then
    tmux new-session -d -s $SESSIONNAME -n Main "cd $DIR/bin && ./safebackfill.sh"
    tmux selectp -t 0
    tmux splitw -h -p 50 "top"
    tmux selectp -t 1
    tmux splitw -v -p 45 "cd $DIR/bin && ./monitor.sh"
    tmux selectp -t 0

    tmux new-window -n Console 'bash -i'
    tmux select-window -t $SESSIONNAME:0
fi
