YUI.add("moodle-mod_quiz-quizquestionbank",function(e,t){var n={QBANKLOADING:"div.questionbankloading",ADDQUESTIONLINKS:"ul.menu a.questionbank",ADDTOQUIZCONTAINER:"td.addtoquizaction"},r={PAGE:"addonpage",HEADER:"header"},i=function(){i.superclass.constructor.apply(this,arguments)};e.extend(i,e.Base,{loadingDiv:"",dialogue:null,addonpage:0,create_dialogue:function(){config={headerContent:"",bodyContent:e.one(n.QBANKLOADING),draggable:!0,modal:!0,centered:!0,width:null,visible:!1,postmethod:"form",footerContent:null,extraClasses:["mod_quiz_qbank_dialogue"]},this.dialogue=new M.core.dialogue(config),this.dialogue.bodyNode.delegate("click",this.link_clicked,".paging a[href], thead tr a[href]",this),this.dialogue.hide(),this.loadingDiv=this.dialogue.bodyNode.getHTML(),e.later(100,this,function(){this.load_content(window.location.search)})},initializer:function(){if(!e.one(n.QBANKLOADING))return;this.create_dialogue(),e.one("body").delegate("click",this.display_dialogue,n.ADDQUESTIONLINKS,this)},display_dialogue:function(e){e.preventDefault(),this.dialogue.set("headerContent",e.currentTarget.getData(r.HEADER)),this.addonpage=e.currentTarget.getData(r.PAGE);var t=this.dialogue.bodyNode.one(".modulespecificbuttonscontainer");if(t){var n=t.one("input[name=addonpage]");n||(n=t.appendChild('<input type="hidden" name="addonpage">')),n.set("value",this.addonpage)}this.dialogue.show()},load_content:function(t){this.dialogue.bodyNode.append(this.loadingDiv),window.history.replaceState&&window.history.replaceState(null,"",M.cfg.wwwroot+"/mod/quiz/edit.php"+t),e.io(M.cfg.wwwroot+"/mod/quiz/questionbank.ajax.php"+t,{method:"GET",on:{success:this.load_done,failure:this.load_failed},context:this})},load_done:function(t,n){var r=JSON.parse(n.responseText);if(!r.status||r.status!=="OK"){this.load_failed(t,n);return}this.dialogue.bodyNode.setHTML(r.contents),e.use("moodle-question-chooser",function(){M.question.init_chooser({})}),this.dialogue.bodyNode.one("form").delegate("change",this.options_changed,".searchoptions",this),this.dialogue.visible&&e.later(0,this.dialogue,this.dialogue.centerDialogue),M.question.qbankmanager.init(),e.one("#advancedsearch")&&M.util.init_collapsible_region(e,"advancedsearch","question_bank_advanced_search",M.util.get_string("clicktohideshow")),this.dialogue.fire("widget:contentUpdate"),this.dialogue.get("visible")&&(this.dialogue.hide(),this.dialogue.show())},load_failed:function(){},link_clicked:function(e){if(e.currentTarget.ancestor(n.ADDTOQUIZCONTAINER)){e.currentTarget.set("href",e.currentTarget.get("href")+"&addonpage="+this.addonpage);return}e.preventDefault(),this.load_content(e.currentTarget.get("search"))},options_changed:function(t){t.preventDefault(),this.load_content("?"+e.IO.stringify(t.currentTarget.get("form")))}}),M.mod_quiz=M.mod_quiz||{},M.mod_quiz.quizquestionbank=M.mod_quiz.quizquestionbank||{},M.mod_quiz.quizquestionbank.init=function(){return new i}},"@VERSION@",{requires:["base","event","node","io","io-form","yui-later","moodle-question-qbankmanager","moodle-core-notification-dialogue"]});
