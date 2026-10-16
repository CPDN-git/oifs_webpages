# oifs_webpages
OpenIFS@home extra webpages including dashboard

The batch statistics and runtime scripts require Python 3. Install their
dependencies into the server's Python environment with:

```sh
python3 -m pip install -r requirements.txt
```

The existing plotting calls are retained. Seaborn is constrained to versions
below 0.14 to keep `distplot` available until the plotting code is reviewed.
