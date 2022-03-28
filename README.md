# Defcon Thread Level #

A Prometheus ready Exporter wich scrapes the current Defcon Thread level from the [](https://www.mi5.gov.uk/). The Exporter is writen in PHP and can placed on every Webserver who can server PHP.


## installation ##

Just clone this Repo and place the index.php into a desired location on your Server.

    git clone https://github.com/vaddi/defcon.git

After the Endpoint is avalable from your Prometheus, you can add them in the as a new Target to your prometheus.yml File:
```
...
  - job_name: 'defcon'
    scrape_interval: 6h
    scrape_timeout: 1m
    metrics_path: '/defcon'
    static_configs:
      - targets: ['host.domain.tld:80']
...
```

### Dependencies ###

- a running PHP Webserver
- PHP >= 7.1
- simpleXML - Neccessary to parse the XML Data.
- A Prometheus instance where you can define this Eporter as an Metrics Endportn

