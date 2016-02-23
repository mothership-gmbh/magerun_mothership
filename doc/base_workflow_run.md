# About

A workflow is a document which encapsulates a state machine.

# Options

* Features new methods

  
  ```
   options:
   
     drink-beer:
       description: "Drink beer? [0,1]"
       required: 1 # none=0, required=2, optional=4, array=8
   
     beer:
       description: 'Which type of beer'
       short: b
       required: 2 # none=0, required=2, optional=4, array=8
       options:
         - Lager
         - Weizen
         - Pils
       default: Weizen
  ```