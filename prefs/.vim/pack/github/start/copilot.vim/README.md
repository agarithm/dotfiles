# GitHub Copilot for Vim and Neovim

<<<<<<< HEAD
GitHub Copilot is an AI pair programmer tool that helps you write code faster
and smarter. Trained on billions of lines of public code, GitHub Copilot turns
natural language prompts including comments and method names into coding
suggestions across dozens of languages.
=======
GitHub Copilot uses OpenAI Codex to suggest code and entire functions in
real-time right from your editor.  Trained on billions of lines of public
code, GitHub Copilot turns natural language prompts including comments and
method names into coding suggestions across dozens of languages.
>>>>>>> master

Copilot.vim is a Vim/Neovim plugin for GitHub Copilot.

To learn more, visit
[https://github.com/features/copilot](https://github.com/features/copilot).

<<<<<<< HEAD
## Getting access to GitHub Copilot

To access GitHub Copilot, an active GitHub Copilot subscription is required.
Sign up for [GitHub Copilot Free](https://github.com/settings/copilot), or
request access from your enterprise admin.
=======
## Subscription

GitHub Copilot requires a subscription.  It is free for verified students and
maintainers of popular open source projects on GitHub.

GitHub Copilot is subject to the [GitHub Additional Product
Terms](https://docs.github.com/en/site-policy/github-terms/github-terms-for-additional-products-and-features).
>>>>>>> master

## Getting started

1.  Install [Neovim][] or the latest patch of [Vim][] (9.0.0185 or newer).

<<<<<<< HEAD
2.  Install [Node.js][].  If you use a package manager, make sure to install
    NPM as well (e.g., `apt install nodejs npm` on Debian/Ubuntu).

3.  Install `github/copilot.vim` using vim-plug, lazy.nvim, or any other
=======
2.  Install [Node.js][].

3.  Install `github/copilot.vim` using vim-plug, packer.nvim, or any other
>>>>>>> master
    plugin manager.  Or to install manually, run one of the following
    commands:

    * Vim, Linux/macOS:

<<<<<<< HEAD
          git clone --depth=1 https://github.com/github/copilot.vim.git \
=======
          git clone https://github.com/github/copilot.vim.git \
>>>>>>> master
            ~/.vim/pack/github/start/copilot.vim

    * Neovim, Linux/macOS:

<<<<<<< HEAD
          git clone --depth=1 https://github.com/github/copilot.vim.git \
=======
          git clone https://github.com/github/copilot.vim.git \
>>>>>>> master
            ~/.config/nvim/pack/github/start/copilot.vim

    * Vim, Windows (PowerShell command):

<<<<<<< HEAD
          git clone --depth=1 https://github.com/github/copilot.vim.git `
=======
          git clone https://github.com/github/copilot.vim.git `
>>>>>>> master
            $HOME/vimfiles/pack/github/start/copilot.vim

    * Neovim, Windows (PowerShell command):

<<<<<<< HEAD
          git clone --depth=1 https://github.com/github/copilot.vim.git `
            $HOME/AppData/Local/nvim/pack/github/start/copilot.vim

4.  Start Vim/Neovim and invoke `:Copilot setup`.
=======
          git clone https://github.com/github/copilot.vim.git `
            $HOME/AppData/Local/nvim/pack/github/start/copilot.vim

4.  Start Neovim and invoke `:Copilot setup`.
>>>>>>> master

[Node.js]: https://nodejs.org/en/download/
[Neovim]: https://github.com/neovim/neovim/releases/latest
[Vim]: https://github.com/vim/vim

Suggestions are displayed inline and can be accepted by pressing the tab key.
See `:help copilot` for more information.

## Troubleshooting

We’d love to get your help in making GitHub Copilot better!  If you have
<<<<<<< HEAD
feedback or encounter any problems, please reach out on our [feedback
forum](https://github.com/github/copilot.vim/issues).
=======
feedback or encounter any problems, please reach out on our [Feedback
forum](https://github.com/orgs/community/discussions/categories/copilot).
>>>>>>> master
