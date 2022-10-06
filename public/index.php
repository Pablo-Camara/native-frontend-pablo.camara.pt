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
//die(date_default_timezone_get()); //die (date('D, d M Y H:i:s ') . 'UTC');
header("Last-Modified: Sun, 04 Sep 2022 18:30:05 UTC");
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
  <link rel="stylesheet" href="assets/css/main.css?v=0.0.0011" />

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
          const lastIgPost = document.getElementById('instagram-last-post');
          lastIgPost.style.display = 'block';
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


  <div id="instagram-last-post" style="display: none">
    <h2 class="dts" data-dts-id="last-ig-post"></h2>
    <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="https://www.instagram.com/reel/CiFvY_-D6Sh/?utm_source=ig_embed&amp;utm_campaign=loading" data-instgrm-version="14" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:16px;"> <a href="https://www.instagram.com/reel/CiFvY_-D6Sh/?utm_source=ig_embed&amp;utm_campaign=loading" style=" background:#FFFFFF; line-height:0; padding:0 0; text-align:center; text-decoration:none; width:100%;" target="_blank"> <div style=" display: flex; flex-direction: row; align-items: center;"> <div style="background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 40px; margin-right: 14px; width: 40px;"></div> <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center;"> <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 100px;"></div> <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 60px;"></div></div></div><div style="padding: 19% 0;"></div> <div style="display:block; height:50px; margin:0 auto 12px; width:50px;"><svg width="50px" height="50px" viewBox="0 0 60 60" version="1.1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-511.000000, -20.000000)" fill="#000000"><g><path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path></g></g></g></svg></div><div style="padding-top: 8px;"> <div style=" color:#3897f0; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:550; line-height:18px;">View this post on Instagram</div></div><div style="padding: 12.5% 0;"></div> <div style="display: flex; flex-direction: row; margin-bottom: 14px; align-items: center;"><div> <div style="background-color: #F4F4F4; border-radius: 50%; height: 12.5px; width: 12.5px; transform: translateX(0px) translateY(7px);"></div> <div style="background-color: #F4F4F4; height: 12.5px; transform: rotate(-45deg) translateX(3px) translateY(1px); width: 12.5px; flex-grow: 0; margin-right: 14px; margin-left: 2px;"></div> <div style="background-color: #F4F4F4; border-radius: 50%; height: 12.5px; width: 12.5px; transform: translateX(9px) translateY(-18px);"></div></div><div style="margin-left: 8px;"> <div style=" background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 20px; width: 20px;"></div> <div style=" width: 0; height: 0; border-top: 2px solid transparent; border-left: 6px solid #f4f4f4; border-bottom: 2px solid transparent; transform: translateX(16px) translateY(-4px) rotate(30deg)"></div></div><div style="margin-left: auto;"> <div style=" width: 0px; border-top: 8px solid #F4F4F4; border-right: 8px solid transparent; transform: translateY(16px);"></div> <div style=" background-color: #F4F4F4; flex-grow: 0; height: 12px; width: 16px; transform: translateY(-4px);"></div> <div style=" width: 0; height: 0; border-top: 8px solid #F4F4F4; border-left: 8px solid transparent; transform: translateY(-4px) translateX(8px);"></div></div></div> <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center; margin-bottom: 24px;"> <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 224px;"></div> <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 144px;"></div></div></a><p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;"><a href="https://www.instagram.com/reel/CiFvY_-D6Sh/?utm_source=ig_embed&amp;utm_campaign=loading" style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none;" target="_blank">A post shared by Pablo (@pablo.elemesmo)</a></p></div></blockquote> <script async src="//www.instagram.com/embed.js"></script>
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