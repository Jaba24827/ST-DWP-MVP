/* =====================================================================
   Progressive enhancement for the Blade screens.
   jQuery is used only where it earns its place: delegated handlers on
   tables that are re-rendered, and the small ajax calls the permission
   matrix makes. The React tier does not load this file.

   Nothing here is a security control. Every action it triggers is
   re-checked on the server.
   ===================================================================== */
(function ($) {
  'use strict';

  var SLDWP = window.SLDWP || {};

  /* CSRF on every ajax request; Laravel refuses the write without it. */
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  /* ---- Theme -------------------------------------------------------- */
  SLDWP.theme = {
    key: 'sldwp.theme',
    apply: function (mode) {
      document.documentElement.setAttribute('data-theme', mode);
      try { localStorage.setItem(this.key, mode); } catch (e) {}
      $('#themeBtn').attr('aria-label', mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    },
    init: function () {
      var stored = null;
      try { stored = localStorage.getItem(this.key); } catch (e) {}
      var system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      this.apply(stored || system);
      var self = this;
      $(document).on('click', '#themeBtn', function () {
        self.apply(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
      });
    }
  };

  /* ---- Sidebar ------------------------------------------------------ */
  SLDWP.nav = {
    init: function () {
      $(document).on('click', '#toggleNav', function () {
        var $app = $('.app');
        if (window.matchMedia('(max-width: 819px)').matches) $app.toggleClass('is-drawer');
        else $app.toggleClass('is-collapsed');
        $(this).attr('aria-expanded', String(!$app.hasClass('is-collapsed')));
      });
      $(document).on('click', '.scrim', function () { $('.app').removeClass('is-drawer'); });
      $(document).on('keydown', function (e) { if (e.key === 'Escape') $('.app').removeClass('is-drawer'); });
    }
  };

  /* ---- Password policy meter --------------------------------------- */
  SLDWP.password = {
    rules: {
      len:    function (v) { return v.length >= 12; },
      upper:  function (v) { return /[A-Z]/.test(v); },
      lower:  function (v) { return /[a-z]/.test(v); },
      digit:  function (v) { return /\d/.test(v); },
      symbol: function (v) { return /[^A-Za-z0-9\s]/.test(v); }
    },
    init: function () {
      var self = this;
      $(document).on('input', '#password, #password_confirmation', function () {
        var v = $('#password').val() || '';
        var met = 0;
        $.each(self.rules, function (name, test) {
          var ok = test(v);
          if (ok) met++;
          $('#pwRules li[data-rule="' + name + '"]').toggleClass('is-met', ok);
        });
        var match = v.length > 0 && v === ($('#password_confirmation').val() || '');
        $('#pwRules li[data-rule="match"]').toggleClass('is-met', match);

        var pct = Math.round((met / 5) * 100);
        $('#pwMeter').css({
          width: pct + '%',
          background: pct < 60 ? 'var(--brick)' : (pct < 100 ? 'var(--gold)' : 'var(--brand)')
        });
        $('#pwSubmit').prop('disabled', !(pct === 100 && match));
      });
    }
  };

  /* ---- Permission matrix (delegated; the table is re-rendered) ------- */
  SLDWP.matrix = {
    init: function () {
      $(document).on('change', '.matrix input[type=checkbox]', function () {
        var $form = $(this).closest('form');
        $form.attr('aria-busy', 'true');
        $.post($form.attr('action'), $form.serialize() + '&_method=PATCH')
          .done(function () { SLDWP.flash('Permissions updated.'); })
          .fail(function (xhr) {
            $(this).prop('checked', !$(this).prop('checked'));
            SLDWP.flash(xhr.status === 403
              ? 'Your role does not hold roles.manage. The attempt was logged.'
              : 'That change was refused.', true);
          }.bind(this))
          .always(function () { $form.removeAttr('aria-busy'); });
      });
    }
  };

  /* ---- MFA code field ----------------------------------------------- */
  SLDWP.mfa = {
    init: function () {
      $(document).on('input', '#code', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        if (this.value.length === 6) $('#verifySubmit').focus();
      });
    }
  };

  SLDWP.flash = function (message, isError) {
    var $f = $('<div class="flash"></div>')
      .addClass(isError ? 'flash--error' : 'flash--ok')
      .attr('role', 'status')
      .text(message);
    $('.page').prepend($f);
    setTimeout(function () { $f.fadeOut(200, function () { $(this).remove(); }); }, 4000);
  };

  $(function () {
    SLDWP.theme.init();
    SLDWP.nav.init();
    SLDWP.password.init();
    SLDWP.matrix.init();
    SLDWP.mfa.init();
  });

  window.SLDWP = SLDWP;
})(window.jQuery);
