local copilot = {}

local showDocument = function(err, result, ctx, _)
  local fallback = vim.lsp.handlers['window/showDocument']
  if not fallback or (result.external and vim.g.copilot_browser) then
    return vim.fn['copilot#handlers#window_showDocument'](result)
  else
    return fallback(err, result, ctx, _)
  end
end

<<<<<<< HEAD
copilot.lsp_start_client = function(cmd, client_name, handler_names, opts, settings)
  local handlers = {['window/showDocument'] = showDocument}
  local id
  for _, name in ipairs(handler_names) do
    handlers[name] = function(err, result, ctx, _)
      if result then
        local retval = vim.call('copilot#client#LspHandle', id, { method = name, params = result })
        if type(retval) == 'table' then
          return retval.result, retval.error
        elseif vim.lsp.handlers[name] then
          return vim.lsp.handlers[name](err, result, ctx, _)
=======
copilot.lsp_start_client = function(cmd, handler_names, opts, settings)
  local handlers = {['window/showDocument'] = showDocument}
  local id
  for _, name in ipairs(handler_names) do
    handlers[name] = function(err, result)
      if result then
        local retval = vim.call('copilot#agent#LspHandle', id, { method = name, params = result })
        if type(retval) == 'table' then
          return retval.result, retval.error
>>>>>>> master
        end
      end
    end
  end
  local workspace_folders = opts.workspaceFolders
  if #workspace_folders == 0 then
    workspace_folders = nil
  end
<<<<<<< HEAD
  local start_client = vim.lsp.start_client
  if vim.fn.has('nvim-0.11.2') == 1 then
    start_client = vim.lsp.start
  end
  id = start_client({
    cmd = cmd,
    cmd_cwd = vim.call('copilot#job#Cwd'),
    name = client_name,
=======
  id = vim.lsp.start_client({
    cmd = cmd,
    cmd_cwd = vim.call('copilot#job#Cwd'),
    name = 'copilot',
>>>>>>> master
    init_options = opts.initializationOptions,
    workspace_folders = workspace_folders,
    settings = settings,
    handlers = handlers,
<<<<<<< HEAD
    on_init = function(client, initialize_result)
      vim.call('copilot#client#LspInit', client.id, initialize_result)
    end,
    on_exit = function(code, signal, client_id)
      vim.schedule(function()
        vim.call('copilot#client#LspExit', client_id, code, signal)
=======
    get_language_id = function(bufnr, filetype)
      return vim.call('copilot#doc#LanguageForFileType', filetype)
    end,
    on_init = function(client, initialize_result)
      vim.call('copilot#agent#LspInit', client.id, initialize_result)
      if vim.fn.has('nvim-0.8') == 0 then
        client.notify('workspace/didChangeConfiguration', { settings = settings })
      end
    end,
    on_exit = function(code, signal, client_id)
      vim.schedule(function()
        vim.call('copilot#agent#LspExit', client_id, code, signal)
>>>>>>> master
      end)
    end,
  })
  return id
end

copilot.lsp_request = function(client_id, method, params, bufnr)
  local client = vim.lsp.get_client_by_id(client_id)
  if not client then
    return
  end
  if bufnr == vim.NIL then
    bufnr = nil
  end
  local _, id
<<<<<<< HEAD
  local handler = function(err, result)
    vim.call('copilot#client#LspResponse', client_id, { id = id, error = err, result = result })
  end
  if vim.fn.has('nvim-0.11') == 1 then
    _, id = client:request(method, params, handler, bufnr)
  else
    _, id = client.request(method, params, handler, bufnr)
  end
=======
  _, id = client.request(method, params, function(err, result)
    vim.call('copilot#agent#LspResponse', client_id, { id = id, error = err, result = result })
  end, bufnr)
>>>>>>> master
  return id
end

copilot.rpc_request = function(client_id, method, params)
  local client = vim.lsp.get_client_by_id(client_id)
  if not client then
    return
  end
  local _, id
  _, id = client.rpc.request(method, params, function(err, result)
<<<<<<< HEAD
    vim.call('copilot#client#LspResponse', client_id, { id = id, error = err, result = result })
=======
    vim.call('copilot#agent#LspResponse', client_id, { id = id, error = err, result = result })
>>>>>>> master
  end)
  return id
end

copilot.rpc_notify = function(client_id, method, params)
  local client = vim.lsp.get_client_by_id(client_id)
  if not client then
    return
  end
  return client.rpc.notify(method, params)
end

<<<<<<< HEAD
copilot.did_change_configuration = function(client_id, settings)
  local client = vim.lsp.get_client_by_id(client_id)
  if not client then
    return
  end
  client.settings = settings
  return client.notify('workspace/didChangeConfiguration', { settings = settings })
end

=======
>>>>>>> master
return copilot
