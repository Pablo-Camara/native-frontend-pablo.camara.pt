<!doctype html>
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
  </style>

  <link rel="preload" href="assets/json/translations_en.json" as="fetch" type="application/json" crossorigin />
  <link rel="preload" href="assets/json/translations_es.json" as="fetch" type="application/json" crossorigin />
  <link rel="preload" href="assets/json/translations_pt.json" as="fetch" type="application/json" crossorigin />
  <link rel="stylesheet" href="assets/css/main.css?v=0.0.002" />

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

    window.PabloCamara = {
      isUnderMaintenance: true,
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
              this.currentLanguage = localStorage.getItem('lang');
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

          setLanguageTogglerCurrentFlag: function () {
            var toggleFlag = document.getElementById('language-toggle-flag');

            if (toggleFlag) {
              toggleFlag.setAttribute('src', 'assets/img/flags/flag-' + window.PabloCamara.Components.Language.currentLanguage + '.png');
              toggleFlag.onclick = function () {
                window.PabloCamara.ViewRouter.hideVisibleView();
                window.PabloCamara.Components.Language.animateLanguageSelection(true);
              };
            }
          },

          setLang: function (lang) {
            this.currentLanguage = lang;
            localStorage.setItem('lang', lang);
            this.setLanguageTogglerCurrentFlag();
            //TODO: Save language in session AND/OR database
            this.translateStrings();
          },

          animateLanguageSelection: function (show) {

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
                      }
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
          animateName: function () {
            setTimeout(function () {
              var pabloCamara = document.getElementById('pablocamara');
              window.PabloCamara.Helpers.toggleClassFromChildren(pabloCamara, ['div'], 'start', 'end');
            }, 100);
          }
        },
        SectionList: {
          addedTransitionEndListener: false,
          hide: function () {
            var sectionList = document.getElementById('section-list');
            sectionList.style.display = 'none';
          },
          animateSectionList: function (show) {


            var sectionList = document.getElementById('section-list');
            sectionList.style.display = 'block';
            var targetTagNames = ['div', 'img'];

            var oldClass = show ? 'start' : 'end';
            var newClass = !show ? 'start' : 'end';

            this.totalTransitionsEnded = 0;
            if (this.addedTransitionEndListener === false) {
              window.PabloCamara.Helpers.callbackOnChildrenWithClass(sectionList, targetTagNames, oldClass, function (child) {

                var listener = function (event) {
                  if (child.classList.contains('start')) {
                    window.PabloCamara.Components.SectionList.totalTransitionsEnded++;
                    if (window.PabloCamara.Components.SectionList.totalTransitionsEnded >= 24) {
                      sectionList.style.display = 'none';
                    }
                    //console.log(window.PabloCamara.Components.SectionList.totalTransitionsEnded);
                  }


                };

                child.addEventListener('transitionend', listener);


              });
              this.addedTransitionEndListener = true;
            }


            setTimeout(function () {
              window.PabloCamara.Helpers.toggleClassFromChildren(sectionList, targetTagNames, oldClass, newClass);
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
        }
      },
      Views: {
        homePage: function (show) {
          
          window.PabloCamara.Components.SectionList.animateSectionList(show);
        },
        underMaintenance: function (show) {
          window.PabloCamara.Components.UnderMaintenance.animate(show);
        }
      },
      ViewRouter: {
        visibleView: null,
        hideVisibleView: function () {
          if (window.PabloCamara.Views.hasOwnProperty(this.visibleView)
            && typeof window.PabloCamara.Views[this.visibleView] === 'function') {
            window.PabloCamara.Views[this.visibleView](false);
            this.visibleView = null;
            return;
          }
          // TODO: View not found 404 alert
        },
        call: function (viewName, viewParam) {

          if(window.PabloCamara.isUnderMaintenance){
            viewName = 'underMaintenance';
            viewParam = true;
          }

          if (window.PabloCamara.Views.hasOwnProperty(viewName)
            && typeof window.PabloCamara.Views[viewName] === 'function') {

            window.PabloCamara.ViewRouter.visibleView = viewName;
            window.PabloCamara.Views[viewName](viewParam);

            return;
          }

          // TODO: View not found 404 alert
        },
        routeAfterLanguageIsSelected: function (viewName, viewParam) {

          if (false === window.PabloCamara.Components.Language.setupLang()) {
            window.PabloCamara.Components.Language.animateLanguageSelection(true);
            return;
          }
          this.call(viewName, viewParam);
        },
        routeWithLanguage: function(lang, viewName, viewParam) {
          if (window.PabloCamara.Components.Language.setupLang(lang)) {
            window.PabloCamara.Components.Language.animateLanguageSelection(false);
            if (false === window.PabloCamara._hasBodyLoaded) {
              document.body.onload = function () {
                window.PabloCamara._hasBodyLoaded = true;
                window.PabloCamara.Components.Loading.end();
                window.PabloCamara.ViewRouter.call(viewName, viewParam);
              };
              return;
            }
            if (window.PabloCamara.Components.Language.setupLang(lang)) {
              window.PabloCamara.ViewRouter.call(viewName, viewParam);
            }
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
  <script type="text/javascript">
    window.PabloCamara.Components.Header.animateName();
    window.PabloCamara.Components.Loading.start();

    
    window.PabloCamara.Components.Language.setFlagsLoadedCallback(function () {
      window.PabloCamara.Components.Loading.end();

      if (null === window.PabloCamara.Components.Language.getLang()) {
        window.PabloCamara.Components.Language.animateLanguageSelection(true);
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


  <div id="language-selector">
    <div class="language first-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('pt','homePage',true)">
      <div class="v-connector load-pt-v-conn start"></div>
      <div class="h-connector load-pt-h-conn start"></div>
      <div class="content load-pt-content start">
        <div class="flag"><img class="load-pt-flag start" src="assets/img/flags/flag-pt.png" /></div>
        <div class="text load-pt-text start">Português</div>
      </div>
    </div>

    <div class="language second-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('en','homePage',true)">
      <div class="v-connector load-en-v-conn start"></div>
      <div class="h-connector load-en-h-conn start"></div>
      <div class="content load-en-content start">
        <div class="flag"><img class="load-en-flag start" src="assets/img/flags/flag-en.png" /></div>
        <div class="text load-en-text start">English</div>
      </div>
    </div>


    <div class="language third-item" onClick="window.PabloCamara.ViewRouter.routeWithLanguage('es','homePage',true)">
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

    <img id="preload-img-4" src="#" data-src="assets/img/section-items/biography.png" />
    <img id="preload-img-5" src="#" data-src="assets/img/section-items/projects.png" />
    <img id="preload-img-6" src="#" data-src="assets/img/section-items/services.png" />
    <img id="preload-img-7" src="#" data-src="assets/img/section-items/blog.png" />
    <img id="preload-img-8" src="#" data-src="assets/img/section-items/portal.png" />
    <img id="preload-img-9" src="#" data-src="assets/img/section-items/contactme.png" />
  </div>

  <script type="text/javascript">
    (function () {
      if (true === window.PabloCamara.isUnderMaintenance) {
        return;
      }

      for (var pic = 4; pic <= 9; pic++) {
        var elId = 'preload-img-' + pic;
        document.getElementById(elId).src = document.getElementById(elId).getAttribute('data-src');
      }
    })();
  </script>
  <!-- Preloading images: end -->


  <div id="under-maintenance" style="display: none;">
    <div class="opacity-animation start">
      <h2 class="dts" data-dts-id="under_maintenance"></h2>
      <p class="dts" data-dts-id="return_later"></p>
      <small class="dts" data-dts-id="contact_if_needed"></small>
    </div>
  </div>

  <div id="section-list" style="display: none">
    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/biography.png" /></div>
      <div class="name start dts" data-dts-id="biography"></div>
    </div>

    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/projects.png" /></div>
      <div class="name start dts" data-dts-id="projects"></div>
    </div>

    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/services.png" /></div>
      <div class="name start dts" data-dts-id="services"></div>
    </div>

    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/blog.png"></div>
      <div class="name start dts" data-dts-id="blog"></div>
    </div>

    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/portal.png">
      </div>
      <div class="name start dts" data-dts-id="web_portal"></div>
    </div>

    <div class="section-item start">
      <div class="image"><img class="start" src="assets/img/section-items/contactme.png"></div>
      <div class="name start dts" data-dts-id="contact_me"></div>
    </div>
  </div>
  </div>

  <script type="text/javascript">

    document.body.onload = function () {
      window.PabloCamara._hasBodyLoaded = true;
      window.PabloCamara.Components.Loading.end();

      if (false === window.PabloCamara.isUnderMaintenance) {
        window.PabloCamara.ViewRouter.routeAfterLanguageIsSelected('homePage', true);
      }
    };
  </script>
</body>

</html>