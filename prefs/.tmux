bind r source-file ~/.tmux

set -g mouse on
set-window-option -g automatic-rename on
set-option -g set-titles on
set -g status-keys vi
set -g history-limit 10000
setw -g mode-keys vi
setw -g monitor-activity on
bind-key Escape copy-mode #default = [
bind-key v split-window -h -c "#{pane_current_path}"
bind-key s split-window -v -c "#{pane_current_path}"
bind-key '"' split-window -v -c "#{pane_current_path}"
bind-key -T copy-mode-vi 'v' send -X begin-selection
bind-key -T copy-mode-vi 'V' send -X select-line;
#LINUX
bind-key -T copy-mode-vi 'y' send -X copy-pipe-and-cancel 'xclip -in -selection clipboard'
bind-key p run "xclip -o -sel clip | tmux load-buffer - ; tmux paste-buffer"

#MacOS Highlight Copy to System
#bind-key -T copy-mode-vi Enter send-keys -X copy-pipe-and-cancel "pbcopy"
#bind-key -T copy-mode-vi MouseDragEnd1Pane send-keys -X copy-pipe-and-cancel "pbcopy"


######################
### DESIGN CHANGES ###
######################

## Status bar design
# status line
set -g status-justify left
set -g status-style bg=default,fg=colour12
set -g status-interval 2

# messaging
set -g message-style fg=black,bg=yellow
set -g message-command-style fg=blue,bg=black

#window mode
setw -g mode-style bg=colour6,fg=colour0

# window status
setw -g window-status-format " #F#I:#W#F "
setw -g window-status-current-format " #F#I:#W#F "
setw -g window-status-format "#[fg=magenta]#[bg=black] #I #[bg=cyan]#[fg=colour8] #W "
setw -g window-status-current-format "#[bg=brightmagenta]#[fg=colour8] #I #[fg=colour8]#[bg=colour14] #W "
setw -g window-status-current-style bg=colour55,fg=colour11,dim
setw -g window-status-style bg=green,fg=black,reverse

# Info on left (I don't have a session display for now)
set -g status-left ''

# loud or quiet?
set-option -g visual-activity off
set-option -g visual-bell off
set-option -g visual-silence off
set-window-option -g monitor-activity off
set-option -g bell-action none

set -g default-terminal "xterm-256color"
set-option -g default-command "/bin/bash --rcfile ~/.bashrc"

# The modes {
setw -g clock-mode-colour colour135
setw -g mode-style fg=colour196,bg=colour238,bold

# }
# The panes {

set -g pane-border-style bg=colour235,fg=colour238
set -g pane-active-border-style bg=colour236,fg=colour51
set -g base-index 1
setw -g pane-base-index 1

# }
# The statusbar {

set -g status-position bottom
set -g status-style bg=colour234,fg=colour137,dim
set -g status-left ''
set -g status-right ''
set -g status-right-length 50
set -g status-left-length 20

setw -g window-status-current-style fg=colour81,bg=colour238,bold
setw -g window-status-current-format ' #I#[fg=colour250]:#[fg=colour255]#W#[fg=colour50]#F '

setw -g window-status-style fg=colour138,bg=colour235,none
setw -g window-status-format ' #I#[fg=colour237]:#[fg=colour250]#W#[fg=colour244]#F '

setw -g window-status-bell-style fg=colour255,bg=colour1,bold

# }
# The messages {

set -g message-style fg=colour232,bg=colour166,bold

# }


