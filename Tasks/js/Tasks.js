/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Tasks');

/**
 * entry point, required by tinebase
 */
Tine.Tasks.getPanel =  function() {
	var tree = Tine.Tasks.mainGrid.getTree();
    tree.on('beforeexpand', function(panel) {
        Tine.Tasks.mainGrid.initComponent();
        Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Tasks.mainGrid.getToolbar());
        Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Tasks.mainGrid.grid);
    }, this);
    
    return tree;
};


// Tasks main screen
Tine.Tasks.mainGrid = {
	/**
     * holds instance of application tree
     */
    tree: null,
    /**
     * holds grid
     */
    grid: null,
    /**
     * holds selection model of grid
     */
    sm: null,
    /**
     * holds underlaying store
     */
    store: null,
    /**
     * holds paging information
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'due',
        dir: 'ASC'
    },
    /**
     * holds current filters
     */
    filter: {
        containerType: 'personal',
        owner: Tine.Tinebase.Registry.get('currentAccount').accountId,
        query: '',
        due: false,
        container: false,
        organizer: false,
        tag: false
    },

    handlers: {
		editInPopup: function(_button, _event){
			var taskId = -1;
			if (_button.actionType == 'edit') {
			    var selectedRows = this.grid.getSelectionModel().getSelections();
                var task = selectedRows[0];
				taskId = task.data.identifier;
			}
			var popupWindow = new Tine.Tasks.EditPopup({
				identifier: taskId
                //relatedApp: 'tasks',
                //relatedId: 
            });
            
            popupWindow.on('update', function(task) {
            	this.store.load({params: this.paging});
            }, this);
            //var popup = Tine.Tinebase.Common.openWindow('TasksEditWindow', 'index.php?method=Tasks.editTask&taskId='+taskId+'&linkingApp=&linkedId=', 700, 300);
            
        },
		deleteTaks: function(_button, _event){
			Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected task(s)', function(_button) {
                if(_button == 'yes') {
				    var selectedRows = this.grid.getSelectionModel().getSelections();
				    if (selectedRows.length < 1) {
				        return;
				    }
					if (selectedRows.length > 1) {
						var identifiers = [];
						for (var i=0; i < selectedRows.length; i++) {
							identifiers.push(selectedRows[i].data.identifier);
						} 
						var params = {
		                    method: 'Tasks.deleteTasks', 
		                    identifiers: Ext.util.JSON.encode(identifiers)
		                };
					} else {
						var params = {
		                    method: 'Tasks.deleteTask', 
		                    identifier: selectedRows[0].data.identifier
		                };
					}
				    
					Ext.Ajax.request({
						scope: this,
		                params: params,
		                success: function(_result, _request) {
		                    this.store.load({params: this.paging});
		                },
		                failure: function ( result, request) { 
		                    Ext.MessageBox.alert('Failed', 'Could not delete task(s).'); 
		                }
		            });
				}
			}, this);
		}
	},
    
        
	initComponent: function() {
    	this.actions = {
            editInPopup: new Ext.Action({
                text: 'edit task',
    			disabled: true,
    			actionType: 'edit',
                handler: this.handlers.editInPopup,
                iconCls: 'action_edit',
                scope: this
            }),
            addInPopup: new Ext.Action({
    			actionType: 'add',
                text: 'add task',
                handler: this.handlers.editInPopup,
                iconCls: 'TasksTreePanel',
                scope: this
            }),
            deleteSingle: new Ext.Action({
                text: 'delete task',
                handler: this.handlers.deleteTaks,
    			disabled: true,
                iconCls: 'action_delete',
                scope: this
            }),
    		deleteMultiple: new Ext.Action({
                text: 'delete tasks',
                handler: this.handlers.deleteTaks,
    			disabled: true,
                iconCls: 'action_delete',
                scope: this
            })
        };
        this.initStore();
        this.initGrid();
	},
	
	initStore: function(){
	    this.store = new Ext.data.JsonStore({
			idProperty: 'identifier',
            root: 'results',
            totalProperty: 'totalcount',
			successProperty: 'status',
			fields: Tine.Tasks.Task,
			remoteSort: true,
			baseParams: {
                method: 'Tasks.searchTasks'
            }
        });
		
		// register store
		Ext.StoreMgr.add('TaskGridStore', this.store);
		
		// prepare filter
		this.store.on('beforeload', function(store, options){
			// console.log(options);
			// for some reasons, paging toolbar eats sort and dir
			if (store.getSortState()) {
				this.filter.sort = store.getSortState().field;
				this.filter.dir = store.getSortState().direction;
			} else {
				this.filter.sort = this.store.sort;
                this.filter.dir = this.store.dir;
			}
			this.filter.start = options.params.start;
            this.filter.limit = options.params.limit;
			
			//this.filter.due
			this.filter.showClosed = Ext.getCmp('TasksShowClosed') ? Ext.getCmp('TasksShowClosed').pressed : false;
			this.filter.organizer = Ext.getCmp('TasksorganizerFilter') ? Ext.getCmp('TasksorganizerFilter').getValue() : '';
			this.filter.query = Ext.getCmp('quickSearchField') ? Ext.getCmp('quickSearchField').getValue() : '';
			this.filter.status = Ext.getCmp('TasksStatusFilter') ? Ext.getCmp('TasksStatusFilter').getValue() : '';
			//this.filter.tag
			options.params.filter = Ext.util.JSON.encode(this.filter);
		}, this);
		
		this.store.on('update', function(store, task, operation) {
			switch (operation) {
				case Ext.data.Record.EDIT:
					Ext.Ajax.request({
    					scope: this,
    	                params: {
    	                    method: 'Tasks.saveTask', 
    	                    task: Ext.util.JSON.encode(task.data),
    						linkingApp: '',
    						linkedId: ''					
    	                },
    	                success: function(_result, _request) {
    						store.commitChanges();
    
    						// we need to reload store, cause this.filters might be 
    						// affected by the change!
    						store.load({params: this.paging});
    	                },
    	                failure: function ( result, request) { 
    	                    Ext.MessageBox.alert('Failed', 'Could not save task.'); 
    	                }
    				});
				break;
				case Ext.data.Record.COMMIT:
				    //nothing to do, as we need to reload the store anyway.
				break;
			}
		}, this);
	   
		this.store.load({
			params: this.paging
		});
	},

	getTree: function() {
        this.tree =  new Tine.widgets.container.TreePanel({
            id: 'TasksTreePanel',
            iconCls: 'TasksTreePanel',
            title: 'Tasks',
            itemName: 'to do lists',
            folderName: 'to do list',
            appName: 'Tasks',
            border: false
        });
        
        
        this.tree.on('click', function(node){
            this.filter.containerType = node.attributes.containerType;
            this.filter.owner = node.attributes.owner ? node.attributes.owner.accountId : null;
            this.filter.container = node.attributes.container ? node.attributes.container.container_id : null;
            
            this.store.load({
                params: this.paging
            });
        }, this);
        
        return this.tree;
    },
    
	// toolbar must be generated each time this fn is called, 
	// as tinebase destroys the old toolbar when setting a new one.
	getToolbar: function(){
		var quickSearchField = new Ext.app.SearchField({
			id: 'quickSearchField',
			width: 200,
			emptyText: 'enter searchfilter'
		});
		quickSearchField.on('change', function(field){
			if(this.filter.query != field.getValue()){
				this.store.load({params: this.paging});
			}
		}, this);
		
		var showClosedToggle = new Ext.Button({
			id: 'TasksShowClosed',
			enableToggle: true,
			handler: function(){
				this.store.load({params: this.paging});
			},
			scope: this,
			text: 'show closed',
			iconCls: 'action_showArchived'
		});
		
		var statusFilter = new Ext.ux.ClearableComboBox({
			id: 'TasksStatusFilter',
			//name: 'statusFilter',
			hideLabel: true,
			store: Tine.Tasks.status.getStore(),
			displayField: 'status_name',
			valueField: 'identifier',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText: 'any',
			selectOnFocus: true,
			editable: false,
			width: 150
		});
		
		statusFilter.on('select', function(combo, record, index){
			this.store.load({params: this.paging});
			combo.triggers[0].show();
		},this);
		
		var organizerFilter = new Tine.widgets.AccountpickerField({
			id: 'TasksorganizerFilter',
			width: 200,
		    emptyText: 'any'
		});
		
		organizerFilter.on('select', function(combo, record, index){
            this.store.load({params: this.paging});
            //combo.triggers[0].show();
        }, this);
		
		var toolbar = new Ext.Toolbar({
			id: 'Tasks_Toolbar',
			split: false,
			height: 26,
			items: [
			    this.actions.addInPopup,
				this.actions.editInPopup,
				this.actions.deleteSingle,
				new Ext.Toolbar.Separator(),
				'->',
				showClosedToggle,
				//'Status: ',	' ', statusFilter,
				//'Organizer: ', ' ',	organizerFilter,
				new Ext.Toolbar.Separator(),
				'->',
				'Search:', ' ', ' ', quickSearchField]
		});
	   
	    return toolbar;
	},
	
    initGrid: function(){
        //this.sm = new Ext.grid.CheckboxSelectionModel();
        var pagingToolbar = new Ext.PagingToolbar({
	        pageSize: 50,
	        store: this.store,
	        displayInfo: true,
	        displayMsg: 'Displaying tasks {0} - {1} of {2}',
	        emptyMsg: "No tasks to display"
	    });
		
		this.grid = new Ext.ux.grid.QuickaddGridPanel({
            id: 'TasksMainGrid',
			border: false,
            store: this.store,
			tbar: pagingToolbar,
			clicksToEdit: 'auto',
            enableColumnHide:false,
            enableColumnMove:false,
            region:'center',
			sm: new Ext.grid.RowSelectionModel(),
			loadMask: true,
            columns: [
				{
					id: 'status',
					header: "Status",
					width: 40,
					sortable: true,
					dataIndex: 'status',
					renderer: Tine.Tasks.status.getStatusIcon,
                    editor: new Tine.Tasks.status.ComboBox({
		                autoExpand: true,
                        blurOnSelect: true,
		                listClass: 'x-combo-list-small'
		            }),
		            quickaddField: new Tine.Tasks.status.ComboBox({
                        autoExpand: true
                    })
				},
				{
					id: 'percent',
					header: "Percent",
					width: 50,
					sortable: true,
					dataIndex: 'percent',
					renderer: Ext.ux.PercentRenderer,
                    editor: new Ext.ux.PercentCombo({
						autoExpand: true,
                        blurOnSelect: true
                    }),
                    quickaddField: new Ext.ux.PercentCombo({
                        autoExpand: true
                    })
				},
				{
					id: 'summaray',
					header: "Summaray",
					width: 400,
					sortable: true,
					dataIndex: 'summaray',
					//editor: new Ext.form.TextField({
					//	allowBlank: false
					//}),
					quickaddField: new Ext.form.TextField({
                        emptyText: 'Add a task...'
                    })
				},
				{
					id: 'priority',
					header: "Priority",
					width: 30,
					sortable: true,
					dataIndex: 'priority',
					renderer: Tine.widgets.Priority.renderer,
                    editor: new Tine.widgets.Priority.Combo({
                        allowBlank: false,
						autoExpand: true,
						blurOnSelect: true
                    }),
                    quickaddField: new Tine.widgets.Priority.Combo({
                        autoExpand: true
                    })
				},
				{
					id: 'due',
					header: "Due Date",
					width: 50,
					sortable: true,
					dataIndex: 'due',
					renderer: Tine.Tinebase.Common.dateRenderer,
					editor: new Ext.ux.ClearableDateField({
                        format : 'd.m.Y'
                    }),
                    quickaddField: new Ext.ux.ClearableDateField({
                        //value: new Date(),
                    	blurOnSelect: true,
                        format : "d.m.Y"
                    })
				}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ],
		    quickaddMandatory: 'summaray',
			autoExpandColumn: 'summaray',
			view: new Ext.grid.GridView({
                autoFill: true,
	            forceFit:true,
	            ignoreAdd: true,
	            emptyText: 'No Tasks to display'
	        })
        });
		
		this.grid.on('rowdblclick', function(grid, row, event){
			this.handlers.editInPopup.call(this, {actionType: 'edit'});
		}, this);
		
		this.grid.getSelectionModel().on('selectionchange', function(sm){
			var disabled = sm.getCount() != 1;
			this.actions.editInPopup.setDisabled(disabled);
			this.actions.deleteSingle.setDisabled(disabled);
			this.actions.deleteMultiple.setDisabled(!disabled);
		}, this);
		
		this.grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
			_eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }

			var numSelected = _grid.getSelectionModel().getCount();
			//if (numSelected < 1) {
			//	return;
			//}
			
			var items = numSelected > 1 ? [this.actions.deleteMultiple] : [
			    this.actions.editInPopup,
                this.actions.deleteSingle,
                '-',
                this.actions.addInPopup
			];
            
			var ctxMenu = new Ext.menu.Menu({
		        //id:'ctxMenuAddress1', 
		        items: items
		    });
            ctxMenu.showAt(_eventObject.getXY());
        }, this);
		
		this.grid.on('keydown', function(e){
	         if(e.getKey() == e.DELETE && !this.grid.editing){
	             this.handlers.deleteTaks.call(this);
	         }
	    }, this);
        		
	    this.grid.on('newentry', function(taskData){
	    	var selectedNode = this.tree.getSelectionModel().getSelectedNode();
            taskData.container = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.container_id : -1;
	        var task = new Tine.Tasks.Task(taskData);

	        Ext.Ajax.request({
	        	scope: this,
                params: {
                    method: 'Tasks.saveTask', 
                    task: Ext.util.JSON.encode(task.data),
                    linkingApp: '',
                    linkedId: ''
                },
                success: function(_result, _request) {
                    Ext.StoreMgr.get('TaskGridStore').load({params: this.paging});
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Could not save task.'); 
                }
            });
            return true;
	    }, this);
	    
		// hack to get percentage editor working
		this.grid.on('rowclick', function(grid,row,e) {
			var cell = Ext.get(grid.getView().getCell(row,1));
			var dom = cell.child('div:last');
			while (cell.first()) {
				cell = cell.first();
				cell.on('click', function(e){
					e.stopPropagation();
					grid.fireEvent('celldblclick', grid, row, 1, e);
				});
			}
		}, this);
    }    
};


Tine.Tasks.EditDialog = function(task) {
	
	if (!arguments[0]) {
		task = {};
	}
	
	// init task record 
    task = new Tine.Tasks.Task(task);
    Tine.Tasks.fixTask(task);
    
	var handlers = {        
        applyChanges: function(_button, _event) {
			var closeWindow = arguments[2] ? arguments[2] : false;
			
			var dlg = Ext.getCmp('TasksEditFormPanel');
			var form = dlg.getForm();
			form.render();
	
			if(form.isValid()) {
				Ext.MessageBox.wait('please wait', 'saving task');
				
					// merge changes from form into task record
				form.updateRecord(task);
				
	            Ext.Ajax.request({
					params: {
		                method: 'Tasks.saveTask', 
		                task: Ext.util.JSON.encode(task.data),
						linkingApp: formData.linking.link_app1,
						linkedId: formData.linking.link_id1 //,
						//jsonKey: Tine.Tinebase.Registry.get('jsonKey')
		            },
		            success: function(_result, _request) {
		                
						dlg.action_delete.enable();
						// override task with returned data
						task = new Tine.Tasks.Task(Ext.util.JSON.decode(_result.responseText));
						Tine.Tasks.fixTask(task);
						
						// update form with this new data
						form.loadRecord(task);                    
						window.ParentEventProxy.fireEvent('update', task);

						if (closeWindow) {
							window.ParentEventProxy.purgeListeners();
                            window.setTimeout("window.close()", 1000);
                        } else {
                        	Ext.MessageBox.hide();
                        }
		            },
		            failure: function ( result, request) { 
		                Ext.MessageBox.alert('Failed', 'Could not save task.'); 
		            } 
				});
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
		},
		saveAndClose:  function(_button, _event) {
			handlers.applyChanges(_button, _event, true);
		},
		pre_delete: function(_button, _event) {
			Ext.MessageBox.confirm('Confirm', 'Do you really want to delete this task?', function(_button) {
                if(_button == 'yes') {
			        Ext.MessageBox.wait('please wait', 'saving task');
	    			Ext.Ajax.request({
	                    params: {
	    					method: 'Tasks.deleteTask',
	    					identifier: task.data.identifier
	    				},
	                    success: function(_result, _request) {
	    					window.ParentEventProxy.fireEvent('update', task);
	    					window.ParentEventProxy.purgeListeners();
	    					window.setTimeout("window.close()", 1000);
	                    },
	                    failure: function ( result, request) { 
	                        Ext.MessageBox.alert('Failed', 'Could not delete task(s).');
	    					Ext.MessageBox.hide();
	                    }
	    			});
				}
			});
		}
	};
	
	var taskFormPanel = {
		layout:'column',
		labelWidth: 90,
		border: false,

		items: [{
            columnWidth: 0.65,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%',
                xtype: 'textfield'
            },
			items:[{
				fieldLabel: 'summaray',
				hideLabel: true,
				xtype: 'textfield',
				name: 'summaray',
				emptyText: 'enter short name...',
				allowBlank: false
			}, {
				fieldLabel: 'notes',
				hideLabel: true,
                emptyText: 'enter description...',
				name: 'description',
				xtype: 'textarea',
				height: 150
			}]
		}, {
            columnWidth: 0.35,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%'
            },
            items:[ 
                new Ext.ux.PercentCombo({
                    fieldLabel: 'Percentage',
                    editable: false,
                    name: 'percent'
                }), 
                new Tine.Tasks.status.ComboBox({
                    fieldLabel: 'Status',
                    name: 'status'
                }), 
                new Tine.widgets.Priority.Combo({
                    fieldLabel: 'Priority',
                    name: 'priority'
                }), 
                new Ext.ux.ClearableDateField({
                    fieldLabel: 'Due date',
                    name: 'due',
                    format: "d.m.Y"
                }), 
                new Tine.widgets.container.selectionComboBox({
                    fieldLabel: 'Folder',
                    name: 'container',
                    itemName: 'Tasks',
                    appName: 'Tasks',
                })
            ]
        }]
	};
	
	var dlg = new Tine.widgets.dialog.EditRecord({
        id : 'TasksEditFormPanel',
        handlerApplyChanges: handlers.applyChanges,
        handlerSaveAndClose: handlers.saveAndClose,
        handlerDelete: handlers.pre_delete,
        labelAlign: 'side',
        layout: 'fit',
        items: taskFormPanel
    });
	
	var viewport = new Ext.Viewport({
        layout: 'border',
        items: dlg
    });
    
    // load form with initial data
    dlg.getForm().loadRecord(task);
    
    if(task.get('identifier') > 0) {
        dlg.action_delete.enable();
    }
};

// generalised popup
Tine.Tasks.EditPopup = Ext.extend(Ext.ux.PopupWindow, {
   relatedApp: '',
   relatedId: -1,
   identifier: -1,
   
   name: 'TasksEditWindow',
   width: 700,
   height: 300,
   initComponent: function(){
        this.url = 'index.php?method=Tasks.editTask&taskId=' + this.identifier + '&linkingApp='+ this.relatedApp + '&linkedId=' + this.relatedId;
        Tine.Tasks.EditPopup.superclass.initComponent.call(this);
   }
});

// fixes a task
Tine.Tasks.fixTask = function(task) {
	if (task.data.container) {
        task.data.container = Ext.util.JSON.decode(task.data.container);
    }
    if (task.data.due) {
        task.data.due = Date.parseDate(task.data.due, 'c');
    }
}

// Task model
Tine.Tasks.Task = Ext.data.Record.create([
    // tine record fields
    { name: 'container' },
    { name: 'created_by' },
    { name: 'creation_time', type: 'date', dateFormat: 'c' },
    { name: 'last_modified_by' },
    { name: 'last_modified_time', type: 'date', dateFormat: 'c' },
    { name: 'is_deleted' },
    { name: 'deleted_time', type: 'date', dateFormat: 'c' },
    { name: 'deleted_by' },
    // task only fields
    { name: 'identifier' },
    { name: 'percent' },
    { name: 'completed', type: 'date', dateFormat: 'c' },
    { name: 'due', type: 'date', dateFormat: 'c' },
    // ical common fields
    { name: 'class' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'priority' },
    { name: 'status' },
    { name: 'summaray' },
    { name: 'url' },
    // ical common fields with multiple appearance
    { name: 'attach' },
    { name: 'attendee' },
    { name: 'categories' },
    { name: 'comment' },
    { name: 'contact' },
    { name: 'related' },
    { name: 'resources' },
    { name: 'rstatus' },
    // scheduleable interface fields
    { name: 'dtstart', type: 'date', dateFormat: 'c' },
    { name: 'duration', type: 'date', dateFormat: 'c' },
    { name: 'recurid' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    { name: 'exrule' },
    { name: 'rdate' },
    { name: 'rrule' }
]);
	
