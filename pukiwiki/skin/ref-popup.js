// PukiWiki2026 - #ref popup (lightbox) for images
// License: GPL v2 or (at your option) any later version
/* eslint-env browser */
window.addEventListener && window.addEventListener('DOMContentLoaded', function () {
  'use strict'

  var overlay = null

  function closePopup () {
    if (!overlay) return
    overlay.classList.add('ref-popup-overlay--closing')
    var node = overlay
    overlay = null
    window.setTimeout(function () {
      if (node.parentNode) {
        node.parentNode.removeChild(node)
      }
    }, 150)
    document.removeEventListener('keydown', onKeydown)
  }

  function onKeydown (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      e.preventDefault()
      closePopup()
    }
  }

  function openPopup (src, alt) {
    closePopup()
    overlay = document.createElement('div')
    overlay.className = 'ref-popup-overlay'
    overlay.setAttribute('role', 'dialog')
    overlay.setAttribute('aria-modal', 'true')
    overlay.setAttribute('aria-label', alt || 'Image preview')

    var img = document.createElement('img')
    img.src = src
    img.alt = alt || ''
    overlay.appendChild(img)

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        closePopup()
      }
    })
    img.addEventListener('click', function (e) {
      e.stopPropagation()
    })

    document.body.appendChild(overlay)
    document.addEventListener('keydown', onKeydown)
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest ? e.target.closest('a.ref-popup-trigger') : null
    if (!trigger) return
    e.preventDefault()
    var href = trigger.getAttribute('href')
    if (!href) return
    var img = trigger.querySelector('img')
    var alt = img ? img.getAttribute('alt') : ''
    openPopup(href, alt)
  })
})
