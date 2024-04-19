
if you upgrade after version 2024-03-25  run on the database:

```
UPDATE ctl_caves SET `ctl_caves`.`marine`='Y' WHERE `marine`='S';
UPDATE ctl_caves SET `ctl_caves`.`archeological`='Y' WHERE `archeological`='S';
UPDATE ctl_caves SET `ctl_caves`.`lake`='Y' WHERE `lake`='S';
UPDATE ctl_caves SET `ctl_caves`.`environmentalrisk`='Y' WHERE `environmentalrisk`='S';
UPDATE ctl_caves SET `ctl_caves`.`tourist`='Y' WHERE `tourist`='S' OR  `tourist`='X';
UPDATE ctl_caves SET `ctl_caves`.`closed`='Y' WHERE `closed`='S' OR `closed`='X';
UPDATE ctl_caves SET `ctl_caves`.`destroyed`='Y' WHERE `destroyed`='S' OR `destroyed`='X';
UPDATE ctl_caves SET `ctl_caves`.`environmentalrisk`='Y' WHERE `environmentalrisk`='S' OR `environmentalrisk`='X';
```

