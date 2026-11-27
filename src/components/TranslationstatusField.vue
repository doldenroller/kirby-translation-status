<template>

    <k-field
        :help="help"
        :input="false"
        :label="label"
        :name="name"
        :type="type"
        class="k-translationstatus-field"
    >

      <k-box v-if="template && templateName" :theme="'translation-head'">
        <k-text>
          <p><strong>Template:</strong> <k-icon :type="templateName.icon" color="gray-800" class="k-info-icon"></k-icon> {{ templateName.name }}</p>
        </k-text>
      </k-box>

      <k-box v-if="translated && showlist" :theme="'translated'"">
        <k-text v-if="extend || untranslated.length > 0">
          <p><strong>{{ finished }}</strong></p>
        </k-text>

        <k-text v-else>
          <p><strong>{{ allfinished }}</strong></p>
        </k-text>

        <k-text v-if="extend || untranslated.length > 0">
          <ul>
            <li v-for="item in translated">
              <k-button-group>
                <k-button v-if="item.code == current" :text="item.name" @click="change(item.code)" current="true" variant="dimmed" />
                <k-button v-else :text="item.name" @click="change(item.code)" :title="item.title" />

                <k-button v-if="item.deleteable && item.notdefault" icon="trash" @click.stop="deleteTranslationOpen(item.name, item.code, $t('translations.delete.confirm'))" variant="filled" theme="negative-icon" size="xs" class="k-translations-button" />

              </k-button-group>

            </li>
          </ul>
        </k-text>
      </k-box>

      <k-box v-if="untranslated.length > 0" :theme="'not-translated'"">
        <k-text>
          <p><strong>{{ unfinished }}</strong></p>
        </k-text>

        <k-text>
          <ul>
            <li v-for="item in untranslated">
              <k-button-group>
                <k-button v-if="item.code == current" :text="item.name" @click="change(item.code)" current="true" variant="dimmed" />
                <k-button v-else :text="item.name" @click="change(item.code)" :title="item.title" />
              </k-button-group>
            </li>
          </ul>
        </k-text>
      </k-box>

    </k-field>
</template>

<script>
export default {
    inheritAttrs: false,
    props: {
      // from DisplayField
      help: String,
      label: String,
      name: String,
      type: String,

      // from TranslationstatusField
      empty: String,
      finished: String,
      unfinished: String,
      allfinished: String,
      template: Boolean,
      extend: Boolean,

      // added by the Panel
      endpoints: Object
    },
    data() {
      return {
        translated: [],
        untranslated: [],
        templateName: [],
        current: []
      };
    },
    methods: {
      change(language) {
        this.$emit("change", language);
        this.$go(window.location, {
          query: {
            language: language
          },
        });
        this.current = language;
      },

      // delete method
      deleteTranslationOpen(language, code, text) {

          this.dialogLanguage = code;
          let dialogText = text + ' (' + language + ')';
          this.$panel.dialog.open({
            component: 'k-remove-dialog',
            props: {
              text: dialogText,
            },
            on: {
              submit: () => {
                this.$api.post('plugin-translationstatus/delete', {id: window.panel.view.path, languageCode: code})
                  .then(response => {
                    if (response.code === 200) {
                      window.panel.notification.success(response.text);
                      //console.log(window.panel.view);
                      this.change(response.default);
                      // not the finest solution, but at least no error. there should be a way to updated only the section but i don't know how...
                      window.setTimeout(() => {
                        location.reload();
                      }, 300);

                    }
                    else {
                      window.panel.notification.error(response.text);
                    }
                  })
                .catch(error => {
                  console.log(error);
                  window.panel.notification.error(error);
                });
              } // submit
            }
          }) // dialog

      }

    },


    async created() {
      const response = await this.$api.get(this.endpoints.field);
      this.templateName = response.templateName;
      this.translated = response.translations.finished;
      this.untranslated = response.translations.unfinished;
      this.showlist = response.translations.show;
      this.current = window.panel.language.code;

      window.panel.events.on('model.update', () => {
        if(!window.panel.language.isDefault){
          if(this.untranslated !== null && this.untranslated.findIndex((x) => x.code === window.panel.language.code) !== -1 ){
            console.log('SAVE', this.untranslated);
            window.setTimeout(() => {
              location.reload();
            }, 100);
          }
        }
      });

    },


};
</script>


<style>
.k-box:not([data-theme="none"]) {border-left: 2px solid var(--color-gray-500); border-radius: 0;}
.k-box:not([data-theme="none"]):first-of-type {border-top-left-radius: var(--rounded); border-top-right-radius: var(--rounded);}
.k-box:not([data-theme="none"]):last-of-type {border-bottom-left-radius: var(--rounded); border-bottom-right-radius: var(--rounded);}

.k-box[data-theme="translation-head"] {background: var(--color-gray-400);}
.k-box[data-theme="translated"] {background: var(--color-gray-100); border-left-color: var(--color-green-400) !important;}
.k-box[data-theme="not-translated"] {background: var(--color-gray-100); border-left-color: var(--color-orange-400) !important;}

.k-box[data-theme="translated"] + .k-box[data-theme="not-translated"] {border-top: 2px solid var(--color-gray-400);}

.k-text ol, .k-text ul {padding-inline-start: 1em;}
.k-box .k-button {padding-inline-start: 0;}

.k-translationstatus-field .k-box {display: block;}
.k-translationstatus-field .k-text {width: 100%;}
.k-translationstatus-field .k-button-group {justify-content: space-between;}
.k-translationstatus-field .k-button[aria-current] {text-decoration: underline;}

.k-info-icon {display: inline-flex; vertical-align: text-bottom;}
</style>
