git config --global core.pager "diff-so-fancy | less -SrRFX --pattern '^(Date|added|deleted|modified):'"
git config --global color.ui true
git config --global core.editor "vim"

git config --global diff.tool vimdiff
git config --global difftool.prompt false
git config --global alias.dt difftool
git config --global merge.tool vimdiff3  #vimdiff3 means 3rd layout option, only merged file is shown
git config --global mergetool.prompt false
git config --global alias.mt mergetool
git config --global alias.co checkout
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
git config --global user.name "Mike Agar"
git config --global user.email "mike.agar@jdpa.com"
git config --global push.autoSetupRemote true
