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
make_link $DOT_BASE/.ctags ~/.ctags

cd ~
sudo apt-get update
sudo apt-get -y upgrade
sudo apt-get dist-upgrade
sudo apt-get install unattended-upgrades mc git tmux python cmake python-dev python-pip build-essential silversearcher-ag php-pear php-cli exuberant-ctags libssl-dev python2.7 nodejs npm

sudo pear install doc.php.net/pman

mkdir ~/projects
cd ~/projects

git clone https://github.com/codota/tabnine-vim.git
cd tabnine-vim
sudo ./install.py

git clone --depth 1 https://github.com/junegunn/fzf.git ~/.fzf
sudo ~/.fzf/install

# diff-so-fancy
git clone git@github.com:so-fancy/diff-so-fancy.git ~/.dsf
git config --global core.pager "~/.dsf/diff-so-fancy | less -SrRFX --pattern '^(Date|added|deleted|modified):'"
git config --global color.ui true

git config --global diff.tool vimdiff
git config --global difftool.prompt false
git config --global alias.dt difftool
git config --global merge.tool vimdiff
git config --global mergetool.prompt false
git config --global alias.mt mergetool
git config --global color.diff-highlight.oldNormal    "red bold"
git config --global color.diff-highlight.oldHighlight "220 88"
git config --global color.diff-highlight.newNormal    "green bold"
git config --global color.diff-highlight.newHighlight "220 22"

git config --global color.diff.meta       "14"
git config --global color.diff.frag       "magenta bold"
git config --global color.diff.commit     "yellow bold"
git config --global color.diff.old        "red bold"
git config --global color.diff.new        "green bold"
git config --global color.diff.whitespace "red reverse"
