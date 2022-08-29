<?php
require_once '../vendor/autoload.php';
$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->load('../.env');


$authServerApiUrl = $_ENV['AUTH_SERVER_API_URL'];

if (!$authServerApiUrl) {
  die('Auth Server Api Url environment variable is not set.');
}

$requestUri = $_SERVER['REQUEST_URI'];

$uriParts = explode('?', $requestUri);
$route = htmlspecialchars($uriParts[0], ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Pablo Câmara</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    html,
    body {
      font-family: Arial, sans-serif;
    }

    body.with-default-bg {
      background-color: #00202B;
    }

    #loading {
      text-align: center;
      padding: 10px;
      color: #00202b;
    }

    /* putting these opacity transitions css here
       because of a bug in google chrome
       that would show images with full opacity on the first load
       in incognito mode, next loads would be fine
       but I assume first load is the more important 
       so this is the fix.
       
       also for the copyright text,
       opacity transitions has bugs in chrome.
     */
    #language-selector .language .flag img.load-pt-flag {
      will-change: opacity;
      transition: opacity 3s;
      transition-timing-function: ease-in-out;
    }
    #language-selector .language .flag img.load-pt-flag.start {
      opacity: 0;
    }
    #language-selector .language .flag img.load-pt-flag.end {
      opacity: 1;
      transition-delay: 3.5s;
    }
    #language-selector .language .flag img.load-en-flag {
      will-change: opacity;
      transition: opacity 3s;
      transition-timing-function: ease-in-out;
    }
    #language-selector .language .flag img.load-en-flag.start {
      opacity: 0;
    }
    #language-selector .language .flag img.load-en-flag.end {
      opacity: 1;
      transition-delay: 3.5s;
    }
    #language-selector .language .flag img.load-es-flag {
      will-change: opacity;
      transition: opacity 2s;
      transition-timing-function: ease-in-out;
    }
    #language-selector .language .flag img.load-es-flag.start {
      opacity: 0;
    }
    #language-selector .language .flag img.load-es-flag.end {
      opacity: 1;
      transition-delay: 4s;
    }

    /* copyright opacity transitions */
    #language-selector #lang-copyright.load-copyright {
      transition: opacity 1s;
      transition-timing-function: ease-in;
    }
    #language-selector #lang-copyright.load-copyright.start {
      opacity: 0;
    }
    #language-selector #lang-copyright.load-copyright.end {
      opacity: 1;
      transition-delay: 3.8s;
    }

  </style>

  <link rel="preload" href="assets/json/translations_en.json" as="fetch" type="application/json" crossorigin />
  <link rel="preload" href="assets/json/translations_es.json" as="fetch" type="application/json" crossorigin />
  <link rel="preload" href="assets/json/translations_pt.json" as="fetch" type="application/json" crossorigin />
  <link rel="stylesheet" href="assets/css/main.css?v=0.0.0010" />

  <script type="text/javascript">

    // make classList.replace work in IE11
    DOMTokenList.prototype.replace = function (a, b) {
        var arr = Array(this);
        var regex = new RegExp(arr.join("|").replace(/ /g, "|"), "i");
        if (!regex.test(a)) {
            return this;
        }
        this.remove(a);
        this.add(b);
        return this;
    }

    window._cookieHelper = {
       setCookie: function (name,value,days) {
          var expires = "";
          if (days) {
              var date = new Date();
              date.setTime(date.getTime() + (days*24*60*60*1000));
              expires = "; expires=" + date.toUTCString();
          }
          document.cookie = name + "=" + (value || "")  + expires + "; path=/";
      },
      getCookie: function (name) {
          var nameEQ = name + "=";
          var ca = document.cookie.split(';');
          for(var i=0;i < ca.length;i++) {
              var c = ca[i];
              while (c.charAt(0)==' ') c = c.substring(1,c.length);
              if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
          }
          return null;
      },
      eraseCookie: function (name) {   
          document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
      }
    };

    window._authManager = {
      at: null,

      isAuthenticated: false,
      isLoggedIn: false,

      api: {
        url: '<?= $authServerApiUrl ?>',
        endpoints: {
          authentication: '/authenticate',
          login: '/login'
        },
      },

      customEvents: {
        userAuthenticatedEvent: null,
        userLoggedInEvent: null,
        userLoginFailed: null
      },

      initialize: function () {
        this.customEvents.userAuthenticatedEvent = document.createEvent('Event');
        this.customEvents.userAuthenticatedEvent.initEvent('userAuthenticated', true, true);

        this.customEvents.userLoggedInEvent = document.createEvent('Event');
        this.customEvents.userLoggedInEvent.initEvent('userLoggedIn', true, true);

        this.customEvents.userLoginFailedEvent = document.createEvent('Event');
        this.customEvents.userLoginFailedEvent.initEvent(
          'userLoginFailed', true, true);
        
        this.authenticate();
      },
      
      
      authenticate: function () {
        var xhr = new XMLHttpRequest();
        xhr.withCredentials = true;

        xhr.addEventListener("readystatechange", function() {
          if(this.status === 200 && this.readyState === 4) {
            const resObj = JSON.parse(this.response);
            window._authManager.at = resObj.at;
            window._authManager.isAuthenticated = true;
            window._authManager.isLoggedIn = resObj.guest ? false : true;

            // trigger userAuthenticated event
            document.dispatchEvent(window._authManager.customEvents.userAuthenticatedEvent);
          }
        });

        xhr.open("POST", this.api.url + this.api.endpoints.authentication);
        xhr.send();
      },
      
      login: function (email, password) {

        if(this.isAuthenticated !== true) {
          // must authenticate as guest first
          return;
        }

        var xhr = new XMLHttpRequest();
        xhr.withCredentials = true;

        xhr.addEventListener("readystatechange", function() {
          if (this.readyState === 4) {

            const resObj = JSON.parse(this.response); //TODO: Catch exception

            if (this.status === 200) {
              window._authManager.at = resObj.at;
              window._authManager.isLoggedIn = resObj.guest ? false : true;

              // trigger userLoggedIn event
              document.dispatchEvent(window._authManager.customEvents.userLoggedInEvent);
            }

            if (this.status === 401) {
              
              if(resObj.error_id === 'incorrect_credentials') {
                // trigger userLoginFailed event
                window._authManager.customEvents.userLoginFailedEvent.reason = resObj.message;
                window._authManager.customEvents.userLoginFailedEvent.isError = true;
                document.dispatchEvent(
                  window._authManager.customEvents.userLoginFailedEvent
                );
              }
              
            }


          }
          
        });

        const credentialsQueryStr = "?email=" + email + "&password=" + password;
        xhr.open("POST", this.api.url + this.api.endpoints.login + credentialsQueryStr);
        xhr.setRequestHeader("Authorization", "Bearer " + this.at);
        xhr.send();
      }
    };

    window._authManager.initialize();

    window.PabloCamara = {
      isUnderMaintenance: false,
      _hasBodyLoaded: false,
      Helpers: {
        toggleClassFromChildren: function (element, childrenTagNames, oldClass, newClass, extraOptions) {
          var totalReplaces = 0;
          for (var x = 0; x < childrenTagNames.length; x++) {
            var childrenToChange = element.getElementsByTagName(childrenTagNames[x]);
            for (var i = 0; i < childrenToChange.length; i++) {
              if (!childrenToChange[i].classList.contains(oldClass)) {
                continue;
              }
              childrenToChange[i].classList.replace(oldClass, newClass);
              totalReplaces++;
            }
          }
          return totalReplaces;
        },
        callbackOnChildrenWithClass: function(element, childrenTagNames, className, callback) {
          var totalAdded = 0;
          for (var x = 0; x < childrenTagNames.length; x++) {
            var childrenToChange = element.getElementsByTagName(childrenTagNames[x]);
            for (var i = 0; i < childrenToChange.length; i++) {
              if (!childrenToChange[i].classList.contains(className)) {
                continue;
              }
              callback(childrenToChange[i]);
              totalAdded++;
            }
          }
          return totalAdded;
        }
      },
      Components: {
        Loading: {
          start: function () {

            var el = document.getElementById('loading');
            el.style.display = 'block';

            this.dotCount = 0;
            clearInterval(this.loadingInterval);
            this.loadingInterval = setInterval(function () {
              if (window.PabloCamara.Components.Loading.dotCount >= 3) {
                window.PabloCamara.Components.Loading.dotCount = 0;
              }

              var dots = '';
              for (var dc = 0; dc <= window.PabloCamara.Components.Loading.dotCount; dc++) {
                dots += '.';
              }

              var el = document.getElementById('loading');
              var lang = window.PabloCamara.Components.Language.getLang();
              var loadingStr = '';
              if (null !== lang) {
                loadingStr = window.PabloCamara.Components.Language.loadingStrings[lang];
              }

              el.innerText = loadingStr + dots;

              window.PabloCamara.Components.Loading.dotCount++;
            }, 300);

          },
          end: function () {
            var el = document.getElementById('loading');
            el.style.display = 'none';
            this.dotCount = 0;
            clearInterval(this.loadingInterval);
          }

        },
        Language: {
          loadingStrings: {
            "pt": "Carregando",
            "en": "Loading",
            "es": "Cargando"
          },
          addedTransitionEndListener: false,
          translationStrings: null,
          currentLanguage: null,
          flagImgsLoaded: 0,
          flagImgLoaded: function () {
            this.flagImgsLoaded++;

            var totalFlags = 0;
            var lang;
            for (lang in this.loadingStrings) {
              totalFlags++;
            }

            if (this.flagImgsLoaded === totalFlags) {
              if (typeof this.flagsLoadedCallback === 'function') {
                this.flagsLoadedCallback();
              }
            }
          },
          setFlagsLoadedCallback: function (callback) {
            this.flagsLoadedCallback = callback;
          },
          getLang: function () {
            if (null === this.currentLanguage) {
              this.currentLanguage = window._cookieHelper.getCookie('lang');
            }
            //TODO: If currentLanguage still === null, attempt fetching from session
            return this.currentLanguage;
          },

          setTranslationStrings: function (translationStrings) {
            this.translationStrings = translationStrings;
          },

          getTranslations: function (callback) {


            var xhr = new XMLHttpRequest();
            xhr.onload = function () {

              if (xhr.status >= 200 && xhr.status < 300) {
                // What do when the request is successful
                // TODO: What todo in case JSON.parse fails ?
                window.PabloCamara.Components.Language.setTranslationStrings(
                  JSON.parse(xhr.response)
                );
                callback();
              }

            };
            xhr.open('GET', 'assets/json/translations_' + this.currentLanguage + '.json');
            xhr.send();
          },

          translateStrings: function () {
            this.getTranslations(function () {
              var contentToTranslate = document.getElementsByClassName('dts');
              for (var i = 0; i < contentToTranslate.length; i++) {
                var stringId = contentToTranslate[i].getAttribute('data-dts-id')
                contentToTranslate[i].innerHTML = window.PabloCamara.Components.Language.translationStrings[stringId];
              }
            });
          },

          getTranslatedString: function (stringId) {
            if (
              window.PabloCamara.Components.Language.translationStrings[stringId]
            ) {
              return window.PabloCamara.Components.Language.translationStrings[stringId];
            }

            return '';
          },

          setLanguageTogglerCurrentFlag: function () {
            var toggleFlag = document.getElementById('language-toggle-flag');

            if (toggleFlag) {
              toggleFlag.setAttribute('src', 'assets/img/flags/flag-' + window.PabloCamara.Components.Language.currentLanguage + '.png');
              toggleFlag.onclick = function () {
                window.PabloCamara.ViewRouter.hideVisibleView();
                const currentViewName = window.PabloCamara.ViewRouter.getViewNameFromRoute();
                window.PabloCamara.Components.Language.animate(true, currentViewName, true);
              };
            }
          },

          setLang: function (lang) {
            this.currentLanguage = lang;
            window._cookieHelper.setCookie('lang', lang, 1);
            this.setLanguageTogglerCurrentFlag();
            //TODO: Save language in session AND/OR database
            this.translateStrings();
          },

          animate: function (show, viewName, viewParam) {

            var languageSelector = document.getElementById('language-selector');
            languageSelector.style.display = 'block';
            var oldClass = show ? 'start' : 'end';
            var newClass = !show ? 'start' : 'end';
            var targetTagNames = ['div', 'img'];

            this.totalTransitionsEnded = 0;
            if (this.addedTransitionEndListener === false) {
              window.PabloCamara.Helpers.callbackOnChildrenWithClass(languageSelector, targetTagNames, oldClass, function (child) {
                var listener = function (event) {
                  if (child.classList.contains('start')) {
                    window.PabloCamara.Components.Language.totalTransitionsEnded++;
                    // TODO: Grab total transitions dynamically
                    if (window.PabloCamara.Components.Language.totalTransitionsEnded >= 25) {
                      languageSelector.style.display = 'none';

                      if (false === window.PabloCamara._hasBodyLoaded) {
                        window.PabloCamara.Components.Loading.start();

                        document.body.onload = function () {
                          window.PabloCamara._hasBodyLoaded = true;
                          window.PabloCamara.Components.Loading.end();
                          window.PabloCamara.ViewRouter.call(viewName, viewParam);
                        };
                        return;
                      }
                      
                      window.PabloCamara.ViewRouter.call(viewName, viewParam);
                    }
                  }


                };

                child.addEventListener('transitionend', listener);


              });
              this.addedTransitionEndListener = true;
            }

            setTimeout(function () {
              window.PabloCamara.Helpers.toggleClassFromChildren(languageSelector, targetTagNames, oldClass, newClass);
            }, 100);



          },

          setupLang: function (lang) {
            if (!lang && this.getLang()) {
              this.setLang(this.currentLanguage);
              return true;
            }

            if (lang) {
              this.setLang(lang);
              return true;
            }

            return false;
          }
        },
        Header: {
          animateName: function (show) {
            setTimeout(function () {
              var pabloCamara = document.getElementById('pablocamara');
              var oldClass = show ? 'start' : 'end';
              var newClass = show ? 'end' : 'start';
              window.PabloCamara.Helpers.toggleClassFromChildren(pabloCamara, ['div'], oldClass, newClass);
            }, 100);
          }
        },
        UnderMaintenance: {
          addedTransitionEndListener: false,
          animate: function (show) {

            var el = document.getElementById('under-maintenance');
            el.style.display = 'block';
            var oldClass = show ? 'start' : 'end';
            var newClass = !show ? 'start' : 'end';
            var targetTagNames = ['div'];

            this.totalTransitionsEnded = 0;
            if (this.addedTransitionEndListener === false) {
              window.PabloCamara.Helpers.callbackOnChildrenWithClass(el, targetTagNames, oldClass, function (child) {

                var listener = function (event) {
                  if (child.classList.contains('start')) {
                    window.PabloCamara.Components.Language.totalTransitionsEnded++;
                    // TODO: Grab total transitions dynamically
                    if (window.PabloCamara.Components.Language.totalTransitionsEnded >= 1) {
                      el.style.display = 'none';
                    }
                  }
                };

                child.addEventListener('transitionend', listener);


              });
              this.addedTransitionEndListener = true;
            }

            setTimeout(function () {
              window.PabloCamara.Helpers.toggleClassFromChildren(el, targetTagNames, oldClass, newClass);
            }, 100);

          },
        },
        LoginBox: {
          hasInitialized: false,
          addedTransitionEndListener: false,
          animate: function (show) {
            this.initialize();
            
            var el = document.getElementById('login-box');
            el.style.display = 'block';
            var oldClass = show ? 'start' : 'end';
            var newClass = !show ? 'start' : 'end';
            var targetTagNames = ['h2'];

            this.totalTransitionsEnded = 0;
            if (this.addedTransitionEndListener === false) {
              window.PabloCamara.Helpers.callbackOnChildrenWithClass(el, targetTagNames, oldClass, function (child) {

                var listener = function (event) {
                  if (child.classList.contains('start')) {
                    window.PabloCamara.Components.LoginBox.totalTransitionsEnded++;
                    // TODO: Grab total transitions dynamically
                    if (window.PabloCamara.Components.LoginBox.totalTransitionsEnded >= 1) {
                      el.style.display = 'none';
                    }
                  }
                };

                child.addEventListener('transitionend', listener);


              });
              this.addedTransitionEndListener = true;
            }

            setTimeout(function () {
              window.PabloCamara.Helpers.toggleClassFromChildren(el, targetTagNames, oldClass, newClass);
            }, 100);
            
          },
          validateEmail: function (email) {
            return String(email)
              .toLowerCase()
              .match(
                /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
              );
          },
          submit: function (loginButton) {

            if (!window._authManager.isAuthenticated) {
              return false;
            }

            const emailEl = document.getElementById('login-email');
            const emailFeedbackEl = document.getElementById('login-email-feedback');

            const passwordEl = document.getElementById('login-password');
            const passwordFeedbackEl = document.getElementById('login-password-feedback');

            const loginBoxFeedbackEl = document.getElementById('login-box-feedback');

            if (!this.validateEmail(emailEl.value)) {
              emailFeedbackEl.style.display = 'block';
              const invalidEmailString = window.PabloCamara.Components.Language.getTranslatedString('invalid-email');
              emailFeedbackEl.innerText = invalidEmailString;
              return;
            } else {
              emailFeedbackEl.style.display = 'none';
              emailFeedbackEl.innerText = '';
            }

            if (passwordEl.value.length === 0) {
              passwordFeedbackEl.style.display = 'block';
              const emptyPasswordString = window.PabloCamara.Components.Language.getTranslatedString('empty-password-error');
              passwordFeedbackEl.innerText = emptyPasswordString;
              return;
            } else {
              passwordFeedbackEl.style.display = 'none';
              passwordFeedbackEl.innerText = '';
            }

            const email = emailEl.value;
            const password = passwordEl.value;

            const loginButtonText = window.PabloCamara.Components.Language.getTranslatedString('login');
            const loggingInButtonText = window.PabloCamara.Components.Language.getTranslatedString('checking-credentials');
            const loggedInButtonText = window.PabloCamara.Components.Language.getTranslatedString('logged-in-btn-text');

            //TODO: translate
            loginButton.innerText = loggingInButtonText;
            document.addEventListener('userLoggedIn', (e) => {
              loginBoxFeedbackEl.innerText = '';
              loginBoxFeedbackEl.style.display = 'none';
              loginButton.innerText = loggedInButtonText;
            }, false);

            document.addEventListener('userLoginFailed', (e) => {
              loginBoxFeedbackEl.innerText = e.reason;
              loginBoxFeedbackEl.style.display = 'block';

              if (e.isError) {
                loginBoxFeedbackEl.classList.add('error');
                loginButton.innerText = loginButtonText;
              }
            }, false);

            window._authManager.login(email, password);

          },
          initialize: function () {
            const loginButton = document.getElementById('login-box-login-button');

            if (false === this.hasInitialized) {
              document.addEventListener('userAuthenticated', (e) => {
                loginButton.classList.remove('disabled');
              }, false);

              this.hasInitialized = true;
            }
            if (window._authManager.isAuthenticated) {
              loginButton.classList.remove('disabled');
            }
          }
        }
      },
      Views: {
        homePage: function (show) {
          
        },
        underMaintenance: function (show) {
          window.PabloCamara.Components.UnderMaintenance.animate(show);
        },
        login: function(show) {
          window.PabloCamara.Components.LoginBox.animate(show);
        }
      },
      ViewRouter: {
        currentRoute: '<?= $route ?>',
        routeMap: { //TODO: translate route, move into translation files
            '/': 'login', //'homePage',
            '/login': 'login'
        },
        visibleView: null,
        getViewNameFromRoute: function () {
          if (this.routeMap[this.currentRoute]) {
            return this.routeMap[this.currentRoute];
          }
          return null;
        },
        hideVisibleView: function () {
          if (this.visibleView !== null 
            && window.PabloCamara.Views.hasOwnProperty(this.visibleView)
            && typeof window.PabloCamara.Views[this.visibleView] === 'function') {
            window.PabloCamara.Views[this.visibleView](false);
            this.visibleView = null;
            return;
          }
        },
        call: function (viewName, viewParam) {

          if(window.PabloCamara.isUnderMaintenance){
            viewName = 'underMaintenance';
            viewParam = true;
          }

          if (window.PabloCamara.Views.hasOwnProperty(viewName)
            && typeof window.PabloCamara.Views[viewName] === 'function') {
              
            window.PabloCamara.ViewRouter.hideVisibleView();
            window.PabloCamara.ViewRouter.visibleView = viewName;
            window.PabloCamara.Views[viewName](viewParam);

            return;
          }

          // TODO: View not found 404 alert
        },
        routeAfterLanguageIsSelected: function (viewName, viewParam) {
          if (false === window.PabloCamara.Components.Language.setupLang()) {
            window.PabloCamara.Components.Language.animate(true, viewName, viewParam);
            return;
          }
          this.call(viewName, viewParam);
        },
        routeWithLanguage: function(lang) {
          if (window.PabloCamara.Components.Language.setupLang(lang)) {
            window.PabloCamara.Components.Language.animate(false);
          }
        },
      }
    };

  </script>


</head>

<body>

  <div id="top-section">
    <div id="pablocamara">
      <!-- First letter P -->
      <div class="loadLeftP start"></div>
      <div class="loadUpperP start"></div>
      <div class="loadLowerP start"></div>
      <div class="loadRightP start"></div>

      <!-- First letter A -->
      <div class="loadLeftA start"></div>
      <div class="loadRightA start"></div>
      <div class="loadTopA start"></div>
      <div class="loadBottomA start"></div>



      <!-- First letter B -->
      <div class="loadLeftB start"></div>
      <div class="loadTopB start"></div>
      <div class="loadTopRightB start"></div>
      <div class="loadMiddleB start"></div>
      <div class="loadBottomB start"></div>
      <div class="loadBottomRightB start"></div>

      <!-- First letter L -->
      <div class="loadLeftL start"></div>
      <div class="loadBottomL start"></div>

      <!-- First letter O -->
      <div class="loadLeftO start"></div>
      <div class="loadRightO start"></div>
      <div class="loadTopO start"></div>
      <div class="loadBottomO start"></div>


      <!-- First letter C -->
      <div class="loadTopC start"></div>
      <div class="loadLeftC start"></div>
      <div class="loadBottomC start"></div>

      <!-- Second letter A -->
      <div class="loadA2Hat1 start"></div>
      <div class="loadA2Hat2 start"></div>
      <div class="loadA2Hat3 start"></div>
      <div class="loadA2Hat4 start"></div>
      <div class="loadA2Hat5 start"></div>
      <div class="loadLeftA2 start"></div>
      <div class="loadRightA2 start"></div>
      <div class="loadTopA2 start"></div>
      <div class="loadBottomA2 start"></div>

      <!-- First letter M -->
      <div class="loadLeftM start"></div>
      <div class="loadMiddleM1 start"></div>
      <div class="loadMiddleM2 start"></div>
      <div class="loadMiddleM3 start"></div>
      <div class="loadMiddleM4 start"></div>
      <div class="loadMiddleM5 start"></div>
      <div class="loadRightM start"></div>

      <!-- Third letter A -->
      <div class="loadLeftA3 start"></div>
      <div class="loadRightA3 start"></div>
      <div class="loadTopA3 start"></div>
      <div class="loadBottomA3 start"></div>

      <!-- First letter R -->
      <div class="loadLeftR start"></div>
      <div class="loadUpperR start"></div>
      <div class="loadMiddleR start"></div>
      <div class="loadTopRightR start"></div>
      <div class="loadBottomRightR1 start"></div>
      <div class="loadBottomRightR2 start"></div>
      <div class="loadBottomRightR3 start"></div>

      <!-- Fourth letter A -->
      <div class="loadLeftA4 start"></div>
      <div class="loadRightA4 start"></div>
      <div class="loadTopA4 start"></div>
      <div class="loadBottomA4 start"></div>

    </div>
  </div>


  <div id="loading" style="display: none;"></div>

  <div id="under-maintenance" style="display: none;">
    <div class="opacity-animation start">
      <h2 class="dts" data-dts-id="under_maintenance"></h2>
      <p class="dts" data-dts-id="return_later"></p>
      <small class="dts" data-dts-id="contact_if_needed"></small>
    </div>
  </div>

  
  <script type="text/javascript">
    window.PabloCamara.Components.Header.animateName(true);
    window.PabloCamara.Components.Loading.start();

    
    window.PabloCamara.Components.Language.setFlagsLoadedCallback(function () {
      window.PabloCamara.Components.Loading.end();

      if (null === window.PabloCamara.Components.Language.getLang()) {
        const viewName = window.PabloCamara.ViewRouter.getViewNameFromRoute();
        const viewParam = true;
        window.PabloCamara.Components.Language.animate(true, viewName, viewParam);
        return;
      }

      if(true === window.PabloCamara.isUnderMaintenance){
        window.PabloCamara.ViewRouter.routeAfterLanguageIsSelected('underMaintenance',true);
      }
      
    });
    
  </script>

  <!-- Preloading images: start -->
  <div
    style="width:1px; height:1px; visibility:hidden; overflow:hidden; position: absolute; top: -100px; left: -100px;">
    <img src="#" id="preload-img-1" data-src="assets/img/flags/flag-pt.png" />
    <img src="#" id="preload-img-2" data-src="assets/img/flags/flag-en.png" />
    <img src="#" id="preload-img-3" data-src="assets/img/flags/flag-es.png" />
  </div>

  <script type="text/javascript">
    (function () {
      for (var pic = 1; pic <= 3; pic++) {
        var elId = 'preload-img-' + pic;
        document.getElementById(elId).onload = function () {
          window.PabloCamara.Components.Language.flagImgLoaded();
        };
        document.getElementById(elId).src = document.getElementById(elId).getAttribute('data-src');
      }
    })();
  </script>
  <!-- Preloading images: end -->


  <div id="language-selector" style="display: none;">
    <div class="language first-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('pt')">
      <div class="v-connector load-pt-v-conn start"></div>
      <div class="h-connector load-pt-h-conn start"></div>
      <div class="content load-pt-content start">
        <div class="flag"><img class="load-pt-flag start" src="assets/img/flags/flag-pt.png" /></div>
        <div class="text load-pt-text start">Português</div>
      </div>
    </div>

    <div class="language second-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('en')">
      <div class="v-connector load-en-v-conn start"></div>
      <div class="h-connector load-en-h-conn start"></div>
      <div class="content load-en-content start">
        <div class="flag"><img class="load-en-flag start" src="assets/img/flags/flag-en.png" /></div>
        <div class="text load-en-text start">English</div>
      </div>
    </div>


    <div class="language third-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('es')">
      <div class="v-connector load-es-v-conn start"></div>
      <div class="h-connector load-es-h-conn start"></div>
      <div class="content load-es-content start">
        <div class="flag"><img class="load-es-flag start" src="assets/img/flags/flag-es.png" /></div>
        <div class="text load-es-text start">Español</div>
      </div>
    </div>

    <div id="lang-copyright" class="load-copyright start">
      Copyright @ <?= date('Y'); ?>
    </div>
  </div>

  <script type="text/javascript">
    if (null !== window.PabloCamara.Components.Language.getLang()) {
      window.PabloCamara.Components.Loading.start();
    }
  </script>
  <!-- Preloading images: start -->
  <div
    style="width:1px; height:1px; visibility:hidden; overflow:hidden; position: absolute; top: -100px; left: -100px;">
<!--
    <img id="preload-img-4" src="#" data-src="assets/img/section-items/biography.png" />
    <img id="preload-img-5" src="#" data-src="assets/img/section-items/projects.png" />
    <img id="preload-img-6" src="#" data-src="assets/img/section-items/services.png" />
    <img id="preload-img-7" src="#" data-src="assets/img/section-items/blog.png" />
    <img id="preload-img-8" src="#" data-src="assets/img/section-items/portal.png" />
    <img id="preload-img-9" src="#" data-src="assets/img/section-items/contactme.png" />
-->
  </div>

  <script type="text/javascript">
    (function () {
      if (true === window.PabloCamara.isUnderMaintenance) {
        return;
      }
      // not yet preloading images because not yet necessary
      return;
      for (var pic = 4; pic <= 9; pic++) {
        var elId = 'preload-img-' + pic;
        document.getElementById(elId).src = document.getElementById(elId).getAttribute('data-src');
      }
    })();
  </script>
  <!-- Preloading images: end -->

  <div id="login-box" style="display: none;">
    <div class="input">
        <div class="label dts" data-dts-id="email"></div>
        <input type="email" id="login-email">
        <p id="login-email-feedback" class="field-feedback" style="display: none"></p>
    </div>
    <div class="input password">
        <div class="label dts" data-dts-id="password"></div>
        <input type="password" id="login-password">
        <p id="login-password-feedback" class="field-feedback" style="display: none"></p>
    </div>
    <p id="login-box-feedback" class="login-box-feedback" style="display: none"></p>
    <div id="login-box-login-button" class="button disabled dts" data-dts-id="login"
      onclick="window.PabloCamara.Components.LoginBox.submit(this);"></div>
  </div>

  <script type="text/javascript">

    document.body.onload = function () {
      window.PabloCamara._hasBodyLoaded = true;
      window.PabloCamara.Components.Loading.end();

      if (false === window.PabloCamara.isUnderMaintenance) {
        const currentViewName = window.PabloCamara.ViewRouter.getViewNameFromRoute();
        window.PabloCamara.ViewRouter.routeAfterLanguageIsSelected(currentViewName, true);
      }
    };
  </script>
</body>

</html>