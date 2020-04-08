define(["suluform/collections/forms"],function(a){"use strict";var b=new a,c={toolbarId:"form-toolbar",listId:"form-list",lastClickedEventSettingsKey:"suluformformLastClicked",endPointUrl:b.url(),toolbarKey:"forms",toolbarSearchFields:["id","title"],fieldsAction:b.fieldsUrl(),eventPrefix:"sulu.form.forms.",translatePrefix:"sulu_form.forms."};return{view:!0,layout:{content:{width:"max"}},header:function(){return{title:c.translatePrefix+"title",noBack:!0,toolbar:{languageChanger:{preSelected:this.options.language},buttons:{add:{},deleteSelected:{}}}}},initialize:function(){this.sandbox.sulu.triggerDeleteSuccessLabel(c.translatePrefix+"success_delete"),this.bindCustomEvents(),this.render()},bindCustomEvents:function(){this.sandbox.on("sulu.toolbar.add",this.add.bind(this)),this.sandbox.on("sulu.toolbar.delete",this.deleteSelected.bind(this)),this.sandbox.on("husky.datagrid.item.click",this.saveLastClickedEvent.bind(this)),this.sandbox.on("husky.datagrid.number.selections",this.activateDeleteButton.bind(this))},activateDeleteButton:function(a){a?this.sandbox.emit("sulu.header.toolbar.item.enable","deleteSelected",!0):this.sandbox.emit("sulu.header.toolbar.item.disable","deleteSelected",!1)},render:function(){this.sandbox.dom.html(this.$el,'<div id="'+c.toolbarId+'"></div><div id="'+c.listId+'"></div>'),this.sandbox.sulu.initListToolbarAndList.call(this,c.toolbarKey,c.fieldsAction+"?locale="+this.options.language,{el:this.$find("#"+c.toolbarId),template:"default",instanceName:this.instanceName},{el:this.$find("#"+c.listId),instanceName:this.instanceName,url:c.endPointUrl+"?locale="+this.options.language+"&flat=true&ghost=true&sortBy=title&sortOrder=asc",resultKey:c.toolbarKey,searchFields:c.toolbarSearchFields,viewOptions:{table:{icons:[{column:"title",icon:"pencil",align:"left",callback:this.edit.bind(this)}],rowClickSelect:!0,highlightSelected:!0,fullWidth:!0,badges:[{column:"title",callback:function(a,b){return a.locale!==this.options.language?(b.title=a.locale,b):!1}.bind(this)}]}}})},add:function(){this.sandbox.emit(c.eventPrefix+"navigate-add")},edit:function(a){this.saveLastClickedEvent(a),this.sandbox.emit(c.eventPrefix+"navigate-to",a)},saveLastClickedEvent:function(a){a&&this.sandbox.sulu.saveUserSetting(c.lastClickedEventSettingsKey,a)},deleteSelected:function(){this.sandbox.emit("husky.datagrid.items.get-selected",function(a){this.sandbox.emit(c.eventPrefix+"delete",a,function(a){this.sandbox.emit("husky.datagrid.record.remove",a)}.bind(this),function(){this.sandbox.emit("sulu.labels.success.show",c.translatePrefix+"delete.success","labels.success")}.bind(this))}.bind(this))}}});