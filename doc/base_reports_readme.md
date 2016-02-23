REPORTS COMMAND
-------------------------------------------
This is a *magerun* command to create a *csv* reports to find all the events and relative observers called for each 
Magento page called in the browser during navigation.

#HOW

- Call from terminal line:
    ```
    magerun mothership:reports:observerstimes
    ```
- the command will wait for your navigation into magento website 
- Exit the command when you will finish with *ctrl+c* or *ctrl+z*

You will find inside the folder *observerlogs* in the root directory a file csv for each page you navigated.

Inside the csv you find all the events with the relative observers(model, method and execution time).

##Option --bootleneck

The option bootleneck gives you an immediate feedback for each observers that take more than 1ms for execution.

In this way you can find easily potential bootlenecks.

Run it with:

```
    magerun mothership:reports:observerstimes --bootleneck=true
```