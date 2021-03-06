set modelines=0
set nomodeline
set nocp
set background=dark
set nowrap
set noswapfile
set nobackup
set nowritebackup
set mouse=a
set ttymouse=sgr
set foldmethod=indent
set foldlevel=30

match ErrorMsg '\s\+$'
hi CursorColumn ctermfg=black ctermbg=darkgray cterm=bold guifg=black guibg=darkgray  gui=bold

" cursor sniper {{{
set updatetime=750

function! MySetCursor()
       set cursorline
"       set cursorcolumn
endfunction
function! MyUnSetCursor()
       set nocursorline
"       set nocursorcolumn
endfunction

augroup CursorSniper
        autocmd!
        au! CursorHold * call MyUnSetCursor()
        au! CursorMoved * call MySetCursor()
        au! CursorMovedI * call MyUnSetCursor()
augroup END
" }}}


" sensible.vim - Defaults everyone can agree on
" Maintainer:   Tim Pope <http://tpo.pe/>
" Version:      1.1

if exists('g:loaded_sensible') || &compatible
        finish
else
        let g:loaded_sensible = 'yes'
endif

if has('autocmd')
        filetype plugin indent on
endif
if has('syntax') && !exists('g:syntax_on')
        syntax enable
endif

" Use :help 'option' to see the documentation for the given option.

set autoindent
set cindent
set smartindent
set backspace=indent,eol,start
set complete-=i
set smarttab

set nrformats-=octal

if !has('nvim') && &ttimeoutlen == -1
        set ttimeout
        set ttimeoutlen=100
endif

set incsearch
" Use <C-L> to clear the highlighting of :set hlsearch.
if maparg('<C-L>', 'n') ==# ''
        nnoremap <silent> <C-L> :nohlsearch<C-R>=has('diff')?'<Bar>diffupdate':''<CR><CR><C-L>
endif

if has("diff") | set diffopt+=iwhite | endif
set diffexpr=DiffW()
function DiffW()
        let opt = ""
        if &diffopt =~ "icase"
                let opt = opt . "-i "
        endif
        if &diffopt =~ "iwhite"
                let opt = opt . "-w " " swapped vim's -b with -w
        endif
        silent execute "!diff -a --binary " . opt .
                                \ v:fname_in . " " . v:fname_new .  " > " . v:fname_out
endfunction
" VimDiff Color Scheme
" See https://stackoverflow.com/questions/2019281/load-different-colorscheme-when-using-vimdiff#17183382
highlight! DiffAdd    cterm=bold ctermfg=10 ctermbg=17 gui=none guifg=bg guibg=Red
highlight! DiffDelete cterm=bold ctermfg=10 ctermbg=17 gui=none guifg=bg guibg=Red
highlight! DiffChange cterm=bold ctermfg=10 ctermbg=17 gui=none guifg=bg guibg=Red
highlight! DiffText   cterm=bold ctermfg=10 ctermbg=88 gui=none guifg=bg guibg=Red

set laststatus=2
set ruler
set wildmenu

if !&scrolloff
        set scrolloff=5
endif
if !&sidescrolloff
        set sidescrolloff=5
endif
set display+=lastline

if &encoding ==# 'latin1' && has('gui_running')
        set encoding=utf-8
endif

if &listchars ==# 'eol:$'
        set listchars=tab:>\ ,trail:-,extends:>,precedes:<,nbsp:+
endif

if v:version > 703 || v:version == 703 && has("patch541")
        set formatoptions+=j " Delete comment character when joining commented lines
endif

if has('path_extra')
        setglobal tags-=./tags tags-=./tags; tags^=./tags;
endif

if &shell =~# 'fish$' && (v:version < 704 || v:version == 704 && !has('patch276'))
        set shell=/bin/bash
endif

set autoread

if &history < 1000
        set history=1000
endif
if &tabpagemax < 50
        set tabpagemax=50
endif
if !empty(&viminfo)
        set viminfo^=!
endif
set sessionoptions-=options

" Allow color schemes to do bright colors without forcing bold.
if &t_Co == 8 && $TERM !~# '^linux\|^Eterm'
        set t_Co=16
endif

" Load matchit.vim, but only if the user hasn't installed a newer version.
if !exists('g:loaded_matchit') && findfile('plugin/matchit.vim', &rtp) ==# ''
        runtime! macros/matchit.vim
endif

inoremap <C-U> <C-G>u<C-U>

" vim:set ft=vim et sw=2:
"
"


if exists('$TMUX')
        let &t_SI = "\<Esc>Ptmux;\<Esc>\e[5 q\<Esc>\\"
        let &t_EI = "\<Esc>Ptmux;\<Esc>\e[2 q\<Esc>\\"
else
        let &t_SI = "\e[5 q"
        let &t_EI = "\e[2 q"
endif

filetype on
autocmd FileType php set keywordprg=pman
autocmd FileType ctp set keywordprg=pman

" Rainbow brackets
let g:rainbow_active = 1 "0 if you want to enable it later via :RainbowToggle

"Syntactic Settings
set statusline+=%#warningmsg#
set statusline+=%{SyntasticStatuslineFlag()}
set statusline+=%*

let g:syntastic_always_populate_loc_list = 0
let g:syntastic_auto_loc_list = 0
let g:syntastic_check_on_open = 1
let g:syntastic_check_on_wq = 0
let g:syntastic_auto_jump = 1

"set rtp+=~/projects/tabnine-vim
set rtp+=~/.fzf

map <F3> :FZF<cr>

" Where to store tag files
let g:gutentags_cache_dir = '~/.vim/gutentags'

let g:gutentags_ctags_exclude = ['*min.css', '*.html', '*min.js', '*.json', '*.xml',
                            \ '*.phar', '*.ini', '*.rst', '*.md', '*.swp',
                            \ '*vendor/*/test*', '*vendor/*/Test*',
                            \ '*vendor/*/fixture*', '*vendor/*/Fixture*',
                            \ '*var/cache*', '*var/log*', '*.git*']

let g:gutentags_project_root = ['.git','.htaccess']
