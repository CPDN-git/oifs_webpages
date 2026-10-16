# OpenIFS webpages and batch scripts

OpenIFS collection of PHP for deployment into the project web pages, and python code to be run by cron under boinc for generation of OpenIFS specific batch stats.

The batch statistics and runtime scripts require Python 3. Requirements are listed in requirements.txt.  

Ensure py3 and requirements are available in a conda environment.

The existing plotting calls are retained. Seaborn is constrained to versions
below 0.14 to keep `distplot` available until the plotting code is reviewed.

## cron implementation

Originally on pandia, the OpenIFS scripts & pages were moved to aurai (production server).

The batch stats should be added to `boinc` crontab as:

```
CPDN_PROJECT=CPDN
LOG_DIR=/storage/www/cpdnboinc/log_aurai
BOINC_HOME=/home/boinc
PYTHON_ENV=/home/boinc/miniconda3/envs/py3new/bin/python

0 */4 * * * $PYTHON_ENV $BOINC_HOME/cpdnboinc/oifs_webpages/oifs_main_batch_status.py CPDN > $LOG_DIR/oifs_main_batch_status.log
```


