define(["mvc/collection","suluform/model/form"],function(a,b){return a({model:b,url:function(){return"/admin/api/forms"},fieldsUrl:function(){return"/admin/api/forms/fields"},parse:function(a){return a._embedded.forms}})});