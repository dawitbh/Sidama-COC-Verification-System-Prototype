// script.js - Vanilla JS for interactions (show/hide password, small accessible behaviors)

document.addEventListener('DOMContentLoaded', function(){
  // Add an "animate" class to root for staggered entrance if user allows animations
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    document.documentElement.classList.add('animate');
    // add gentle float to flags after entrance
    setTimeout(function(){
      var flags = document.querySelectorAll('.flags-row img.flag');
      flags.forEach(function(f){ f.classList.add('float'); });
    }, 1200);
  }

  // Show / hide password
  var pwToggle = document.getElementById('pwToggle');
  var password = document.getElementById('password');
  if (pwToggle && password){
    pwToggle.addEventListener('click', function(){
      var isHidden = password.type === 'password';
      password.type = isHidden ? 'text' : 'password';
      pwToggle.setAttribute('aria-pressed', String(isHidden));
      pwToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      // update visible hint (for screen-reader and sighted users)
      var vis = pwToggle.querySelector('.vis'); if (vis) vis.textContent = isHidden ? 'Hide' : 'Show';
    });
  }

  // Minimal keyboard focus visibility for custom buttons
  document.addEventListener('keyup', function(e){
    if (e.key === 'Tab') document.body.classList.add('show-focus');
  });

  // Form submit: allow default; if you want demo blocking, uncomment the next block
  /*
  var form = document.querySelector('.login-form');
  if (form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      // basic client validation
      var u = document.getElementById('username');
      var p = document.getElementById('password');
      if (!u.value || !p.value){ alert('Please enter username and password.'); return; }
      // remove preventDefault to integrate with backend
    });
  }
  */
});
