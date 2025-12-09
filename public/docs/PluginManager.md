### Explanation

**PluginManager**  
Contains all plugin instances and handles communication between them.

**callFunctions('someMethod', args...)**  
Invokes a method on *every plugin that implements it*. Plugins lacking the method are skipped.

**Plugin Instance**  
Each plugin optionally implements `someMethod()`.  
If yes → method executed.  
If no → plugin skipped.

**callPluginFunction(TargetPluginClass, 'method', args...)**  
Calls a method on a single, specific plugin by class name.

**$this->callFunctions('setRegistry', $this);**  
Passes the PluginManager instance to all plugins that implement `setRegistry()`.  
This allows plugins to access shared services and each other, **without circular dependencies**.
