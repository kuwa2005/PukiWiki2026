// PukiWiki2026 - Edit screen drag-and-drop / paste attach / #ref insert
// License: GPL v2 or (at your option) any later version
/* eslint-env browser */
window.addEventListener && window.addEventListener('DOMContentLoaded', function () {
  'use strict'

  var ATTACH_MIME = 'application/x-pkwk-attach'

  function initEditDragDrop () {
    if (!window.FormData || !window.fetch || !document.querySelector) {
      return
    }
    var propRoot = document.querySelector('#pukiwiki-site-properties')
    if (!propRoot) return
    var pluginNameE = propRoot.querySelector('.plugin-name')
    if (!pluginNameE || pluginNameE.value !== 'edit') return

    var pageNameE = propRoot.querySelector('.page-name')
    if (!pageNameE || !pageNameE.value) return
    var pageName = pageNameE.value

    var editForm = document.querySelector('.edit_form form._plugin_edit_edit_form')
    if (!editForm) return
    var textarea = editForm.querySelector('textarea[name="msg"]')
    if (!textarea) return

    var csrfInput = editForm.querySelector('input[name="pkwk_csrf_token"]')
    var csrfToken = csrfInput ? csrfInput.value : ''

    var propsE = propRoot.querySelector('.site-props')
    if (!propsE || !propsE.value) return
    var siteProps
    try {
      siteProps = JSON.parse(propsE.value)
    } catch (e) {
      return
    }
    var uploadPath = siteProps.base_uri_pathname
    if (!uploadPath) return

    var statusEl = document.createElement('div')
    statusEl.className = 'pkwk-edit-dd-status small'
    statusEl.setAttribute('aria-live', 'polite')
    textarea.parentNode.insertBefore(statusEl, textarea.nextSibling)

    setupTextareaDrop(textarea, pageName, uploadPath, csrfToken, statusEl)
    setupTextareaPaste(textarea, pageName, uploadPath, csrfToken, statusEl)
    setupAttachDragSources(pageName)
  }

  function buildRefText (page, file, currentPage, options) {
    var suffix = options ? ',' + options : ''
    if (page === currentPage) {
      return '#ref(' + file + suffix + ')'
    }
    return '#ref(' + page + '/' + file + suffix + ')'
  }

  function insertAtCursor (textarea, text) {
    var start = textarea.selectionStart
    var end = textarea.selectionEnd
    var val = textarea.value
    textarea.value = val.substring(0, start) + text + val.substring(end)
    var pos = start + text.length
    textarea.selectionStart = pos
    textarea.selectionEnd = pos
    textarea.focus()
  }

  function setStatus (statusEl, message, isError) {
    statusEl.textContent = message || ''
    if (isError) {
      statusEl.classList.add('pkwk-edit-dd-status--error')
    } else {
      statusEl.classList.remove('pkwk-edit-dd-status--error')
    }
  }

  function hasFileItems (dataTransfer) {
    if (!dataTransfer || !dataTransfer.types) return false
    var types = dataTransfer.types
    if (types.indexOf) {
      return types.indexOf('Files') !== -1
    }
    for (var i = 0; i < types.length; i++) {
      if (types[i] === 'Files') return true
    }
    return false
  }

  function pad2 (n) {
    return (n < 10 ? '0' : '') + n
  }

  function mimeToExt (mime) {
    var map = {
      'image/png': '.png',
      'image/jpeg': '.jpg',
      'image/jpg': '.jpg',
      'image/gif': '.gif',
      'image/webp': '.webp'
    }
    return map[mime] || '.png'
  }

  function makePasteFilename (blob, index, total) {
    var now = new Date()
    var stamp = '' + now.getFullYear() +
      pad2(now.getMonth() + 1) +
      pad2(now.getDate()) + '-' +
      pad2(now.getHours()) +
      pad2(now.getMinutes()) +
      pad2(now.getSeconds())
    var ext = mimeToExt(blob.type || 'image/png')
    var suffix = total > 1 ? '-' + (index + 1) : ''
    return 'paste-' + stamp + suffix + ext
  }

  function blobToNamedFile (blob, name) {
    if (typeof File !== 'undefined') {
      try {
        return new File([blob], name, { type: blob.type || 'application/octet-stream' })
      } catch (e) {
        // fall through
      }
    }
    blob.name = name
    return blob
  }

  function collectClipboardImages (clipboardData) {
    var images = []
    if (!clipboardData) {
      return images
    }
    var items = clipboardData.items
    if (items && items.length) {
      for (var i = 0; i < items.length; i++) {
        var item = items[i]
        if (!item.type || item.type.indexOf('image/') !== 0) {
          continue
        }
        var blob = item.getAsFile ? item.getAsFile() : null
        if (blob) {
          images.push(blob)
        }
      }
    }
    if (images.length === 0 && clipboardData.files && clipboardData.files.length) {
      for (var j = 0; j < clipboardData.files.length; j++) {
        var file = clipboardData.files[j]
        if (file.type && file.type.indexOf('image/') === 0) {
          images.push(file)
        }
      }
    }
    return images
  }

  function setupTextareaPaste (textarea, pageName, uploadPath, csrfToken, statusEl) {
    textarea.addEventListener('paste', function (e) {
      var blobs = collectClipboardImages(e.clipboardData)
      if (blobs.length === 0) {
        return
      }
      e.preventDefault()
      var files = blobs.map(function (blob, index) {
        return blobToNamedFile(blob, makePasteFilename(blob, index, blobs.length))
      })
      uploadFiles(files, textarea, pageName, uploadPath, csrfToken, statusEl, 'middle')
    })
  }

  function setupTextareaDrop (textarea, pageName, uploadPath, csrfToken, statusEl) {
    var dragDepth = 0

    function clearDragover () {
      dragDepth = 0
      textarea.classList.remove('pkwk-edit-dragover')
    }

    textarea.addEventListener('dragenter', function (e) {
      if (hasFileItems(e.dataTransfer) || e.dataTransfer.types.indexOf(ATTACH_MIME) !== -1) {
        e.preventDefault()
        dragDepth++
        textarea.classList.add('pkwk-edit-dragover')
      }
    })

    textarea.addEventListener('dragover', function (e) {
      if (hasFileItems(e.dataTransfer)) {
        e.preventDefault()
        e.dataTransfer.dropEffect = 'copy'
        textarea.classList.add('pkwk-edit-dragover')
        return
      }
      if (e.dataTransfer.types.indexOf(ATTACH_MIME) !== -1) {
        e.preventDefault()
        e.dataTransfer.dropEffect = 'copy'
        textarea.classList.add('pkwk-edit-dragover')
      }
    })

    textarea.addEventListener('dragleave', function () {
      dragDepth--
      if (dragDepth <= 0) {
        clearDragover()
      }
    })

    textarea.addEventListener('drop', function (e) {
      e.preventDefault()
      clearDragover()

      var attachRaw = e.dataTransfer.getData(ATTACH_MIME)
      if (attachRaw) {
        try {
          var info = JSON.parse(attachRaw)
          if (info.page && info.file) {
            insertAtCursor(textarea, buildRefText(info.page, info.file, pageName))
            setStatus(statusEl, '')
          }
        } catch (err) {
          setStatus(statusEl, '添付の挿入に失敗しました', true)
        }
        return
      }

      var files = e.dataTransfer.files
      if (!files || files.length === 0) return

      uploadFiles(Array.prototype.slice.call(files), textarea, pageName, uploadPath, csrfToken, statusEl)
    })
  }

  function parseJsonResponse (response) {
    return response.text().then(function (text) {
      try {
        return JSON.parse(text)
      } catch (e) {
        throw new Error('サーバー応答の解析に失敗しました')
      }
    })
  }

  function uploadFiles (files, textarea, pageName, uploadPath, csrfToken, statusEl, refOptions) {
    var index = 0
    var uploadedCount = 0

    function next () {
      if (index >= files.length) {
        if (uploadedCount > 0) {
          setStatus(statusEl, uploadedCount + ' 件アップロードしました')
        }
        return
      }
      var file = files[index]
      index++
      setStatus(statusEl, 'アップロード中: ' + file.name + ' (' + index + '/' + files.length + ')')

      var formData = new FormData()
      formData.append('plugin', 'attach')
      formData.append('pcmd', 'api')
      formData.append('refer', pageName)
      formData.append('attach_file', file)
      if (csrfToken) {
        formData.append('pkwk_csrf_token', csrfToken)
      }

      fetch(uploadPath, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      }).then(function (response) {
        return parseJsonResponse(response).then(function (obj) {
          if (!response.ok || !obj.ok) {
            throw new Error(obj.error || response.statusText || 'upload failed')
          }
          return obj
        })
      }).then(function (obj) {
        var name = obj.filename || file.name
        insertAtCursor(textarea, buildRefText(pageName, name, pageName, refOptions) + '\n')
        uploadedCount++
        if (obj.attach_html) {
          updateAttachList(obj.attach_html)
        }
        next()
      })['catch'](function (err) { // eslint-disable-line dot-notation
        setStatus(statusEl, String(err.message || err), true)
      })
    }

    next()
  }

  function updateAttachList (html) {
    var listEl = document.getElementById('pkwk-attach-list')
    if (listEl) {
      listEl.innerHTML = html
      return
    }
    var attachDiv = document.getElementById('attach')
    if (attachDiv) {
      listEl = document.createElement('span')
      listEl.id = 'pkwk-attach-list'
      listEl.innerHTML = html
      attachDiv.appendChild(listEl)
      return
    }
    attachDiv = document.createElement('div')
    attachDiv.id = 'attach'
    var hr = document.createElement('hr')
    attachDiv.appendChild(hr)
    listEl = document.createElement('span')
    listEl.id = 'pkwk-attach-list'
    listEl.innerHTML = html
    attachDiv.appendChild(listEl)
    var toolbar = document.getElementById('toolbar')
    if (toolbar && toolbar.parentNode) {
      toolbar.parentNode.insertBefore(attachDiv, toolbar)
    } else {
      document.body.appendChild(attachDiv)
    }
  }

  function setupAttachDragSources (currentPage) {
    var attachRoot = document.getElementById('attach')
    if (!attachRoot) return

    attachRoot.addEventListener('dragstart', function (e) {
      var item = e.target.closest ? e.target.closest('.pkwk-attach-draggable') : null
      if (!item) return
      var page = item.getAttribute('data-attach-page')
      var file = item.getAttribute('data-attach-file')
      if (!page || !file) return
      var payload = JSON.stringify({ page: page, file: file })
      e.dataTransfer.setData(ATTACH_MIME, payload)
      e.dataTransfer.setData('text/plain', buildRefText(page, file, currentPage))
      e.dataTransfer.effectAllowed = 'copy'
      item.classList.add('pkwk-attach-dragging')
    })

    attachRoot.addEventListener('dragend', function (e) {
      var item = e.target.closest ? e.target.closest('.pkwk-attach-draggable') : null
      if (item) {
        item.classList.remove('pkwk-attach-dragging')
      }
    })
  }

  initEditDragDrop()
})
