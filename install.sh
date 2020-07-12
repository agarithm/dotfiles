#!/bin/bash

function make_link {
	# $1 = source file (destination for the link)
	# $2 = link name
	SRC=`readlink -f $1`

	if [ -f $2 ]; then 
		echo "Backup $2 to $BACKUP"
		mv -i $2 $BACKUP
	fi

	if [ -d $2 ]; then 
		echo "Backup $2 to $BACKUP"
		mv -i $2 $BACKUP
	fi

	echo "ln -s $1 $2"
	ln -s $SRC $2
}

BACKUP=~/backup/old_dotfiles
mkdir -p $BACKUP
script=`readlink -f $0`
REPO_BASE=`dirname $script`
DOT_BASE=$REPO_BASE/prefs

make_link $REPO_BASE/bin ~/bin
make_link $DOT_BASE/.bashrc ~/.bashrc
make_link $DOT_BASE/.selected_editor ~/.selected_editor
make_link $DOT_BASE/.tmux ~/.tmux
make_link $DOT_BASE/.vimrc ~/.vimrc
make_link $DOT_BASE/.vim ~/.vim

sudo apt-get update
sudo apt-get install unattended-upgrades mc git tmux python cmake python-dev python-pip build-essential silversearcher-ag

mkdir ~/projects
cd ~/projects

git clone https://github.com/codota/tabnine-vim.git
cd tabnine-vim 
./install.py

