#!/usr/bin/env python2.7
#-----------------------------------------------------------------------
# Program: batch_run_time.py 
# Purpose: To display batch queue time and run time.
# Created: Sarah Sparrow 07/12/15
# Details: Run with inputs batch number
#-----------------------------------------------------------------------
import os, sys, time, MySQLdb, hashlib
import numpy as np
import collections
from xml.sax.saxutils import escape  # needed to make our XML safe.
import xml.etree.ElementTree as ET
import matplotlib
matplotlib.use("Agg")
import matplotlib.mlab as mlab
import matplotlib.pyplot as plt
from matplotlib.offsetbox import AnchoredText
import seaborn as sns
from scipy import stats
import time
from datetime import datetime
from socket import gethostname

# List of outcome, client_state, server_state for reference
outcome={'0':'RESULT_OUTCOME_INIT',\
'1' : 'RESULT_OUTCOME_SUCCESS',\
'2' : 'RESULT_OUTCOME_COULDNT_SEND',\
'3' : 'RESULT_OUTCOME_CLIENT_ERROR',\
'4' : 'RESULT_OUTCOME_NO_REPLY',\
'5' : 'RESULT_OUTCOME_DIDNT_NEED',\
'6' : 'RESULT_OUTCOME_VALIDATE_ERROR',\
'7' : 'RESULT_OUTCOME_CLIENT_DETACHED' }

client_state={'0' : 'RESULT_NEW',\
'1' : 'RESULT_FILES_DOWNLOADING',\
'2' : 'RESULT_FILES_DOWNLOADED',\
'3' : 'RESULT_COMPUTE_ERROR',\
'4' : 'RESULT_FILES_UPLOADING',\
'5' : 'RESULT_FILES_UPLOADED',\
'6' : 'RESULT_ABORTED'}

server_state={'1' : 'RESULT_SERVER_STATE_INACTIVE',\
'2' : 'RESULT_SERVER_STATE_UNSENT',\
'3' : 'Unsent_seq',\
'4' : 'RESULT_SERVER_STATE_IN_PROGRESS',\
'5' : 'RESULT_SERVER_STATE_OVER'}

today=time.strftime('%Y-%m-%d')
today_title=time.strftime('%Y-%m-%d %H:%M')

class BatchDB:
        #database connection info - starts empty
        pass

def ParseConfig(xmlfilename):
        doc        = ET.parse(xmlfilename)
        root_node = doc.getroot()

        BatchDB.host=root_node.findtext('db_host')
        BatchDB.batchUser=root_node.findtext('batch_user')
        BatchDB.batchPass=root_node.findtext('batch_passwd')
	BatchDB.dbexpt=root_node.findtext('db_name')
        BatchDB.dbboinc=root_node.findtext('boinc_db_name')

def get_times(batch):
	elapsed_time=[]
	queue_time=[]
	run_time=[]
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
        cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'select (r.sent_time - r.create_time)/3600. as queue_time, (r.received_time - r.sent_time)/3600. as run_time,(r.received_time - r.create_time)/3600. as elapsed_time from '+BatchDB.dbexpt+'.cpdn_workunit w join '+BatchDB.dbboinc+'.result r on r.workunitid=w.wuid where w.cpdn_batch='+str(batch)+' and r.outcome=1;'
        # Get numbers of results in different states.
	cursor.execute(query_batches)
	lbatch=cursor.fetchall()
	for i in range(0,len(lbatch)):
		elapsed_time.append(float(lbatch[i]['elapsed_time']))
		queue_time.append(float(lbatch[i]['queue_time']))
		run_time.append(float(lbatch[i]['run_time']))
	cursor.close()
	return np.array(queue_time),np.array(elapsed_time),np.array(run_time)

def get_percent_complete(batch):
        db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
        cursor = db.cursor(MySQLdb.cursors.DictCursor)

        query_batches2 = 'select (count(r.id)/b.number_of_workunits)*100 as completed from '+BatchDB.dbboinc+'.result r JOIN '+BatchDB.dbexpt+'.cpdn_workunit w on r.workunitid=w.wuid JOIN '+BatchDB.dbexpt+'.cpdn_batch b on b.id=w.cpdn_batch where w.cpdn_batch='+str(batch)+' and r.outcome=1;'
        # Get numbers of results in different states.
        cursor.execute(query_batches2)
        lbatch=cursor.fetchall()
        completed=float(lbatch[0]['completed'])
        cursor.close()
        return completed


def plot_batch_run_time(batch, queue,batch_prefix, out_path):
	# Set the plot font size
	font = {'family' : 'sans-serif','size'   : 14}
	matplotlib.rc('font', **font)

	queue_time,elapsed_time,run_time=get_times(batch)
	percent_complete=get_percent_complete(batch)

	fig = plt.figure()      
	ax=fig.add_subplot(1,1,1)
	
	print "Median: ",np.percentile(elapsed_time,50)
	print "5th Percentile: ", np.percentile(elapsed_time,5)
	print "95th Percentile: ", np.percentile(elapsed_time,95)

	stats_text="50% returned after: "+str(np.round(np.percentile(elapsed_time,50),2))+" hours \n80% returned after: "+str(np.round(np.percentile(elapsed_time,80),2))+" hours \n\nMedian queue time: "+str(np.round(np.percentile(queue_time,50),2))+" hours \nMedian run time: "+str(np.round(np.percentile(run_time,50),2))+" hours"
	# the histogram of the data
	sns.distplot(queue_time,kde=False,fit=stats.genextreme, color="Gold", label="Queue time",fit_kws={"linewidth":2.5,"color":"gold"}, ax=ax)
        sns.distplot(run_time,kde=False,fit=stats.genextreme, color="mediumpurple", label="Run time",fit_kws={"linewidth":2.5,"color":"mediumpurple"},ax=ax)
	ax2=ax.twinx()
	sns.distplot(elapsed_time, hist_kws={'cumulative': True},kde_kws=dict(cumulative=True), color="RoyalBlue", label="Elapsed time (cumulative)", ax=ax2)
	ax.set_title("Batch "+batch_prefix+str(batch)+" Timings: "+str(np.round(percent_complete,2))+"% Complete: "+today_title,fontsize=16)

        anchored_text = AnchoredText(stats_text,loc=7,frameon=False)
        ax2.add_artist(anchored_text)

	lines, labels = ax.get_legend_handles_labels()
	lines2, labels2 = ax2.get_legend_handles_labels()
	ax2.legend(lines + lines2, labels + labels2, loc="lower right",prop={"size": 12},fancybox=True,numpoints=1,framealpha=1)
	ax.set_ylabel("Normalised Occurrence",fontsize=14)
	ax2.set_ylabel("% Sucessful Returns",fontsize=14)
	ax.set_xlabel("Time (Hours)",fontsize=14)
	ax.set_xlim(0,100)
	plt.setp(ax.get_xticklabels(),fontsize=12)
	plt.setp(ax.get_yticklabels(),fontsize=12)
	plt.setp(ax2.get_yticklabels(),fontsize=12)
	plt.tight_layout()
	plt.grid(linestyle=':',zorder=0)
	fig.set_size_inches(12.5, 5)
	fig.savefig(out_path+"Batch_"+batch_prefix+str(batch)+"_timings.png")

	

def main():
	print time.strftime("%Y/%m/%d %H:%M:%S") + " Starting batch_run_time.py"
	dl_path = '/home/cpdn/'
	
	batch=sys.argv[1]
	project=sys.argv[2]
	host=gethostname()

	try:
		queue=sys.argv[3]
	except:
		queue=False

	if project=='CPDN' or host=='caerus' or host=='hesperus':
                config='/storage/www/cpdnboinc/ancil_batch_user_config.xml'
                out_path='/storage/www/cpdnboinc/tmp_batch/'
                batch_prefix=''
        elif project=='CPDN_DEV':
                config='/storage/www/cpdnboinc_dev/ancil_batch_user_config.xml'
                out_path='/storage/www/cpdnboinc_dev/tmp_batch/'
                batch_prefix='d'
        elif project=='CPDN_ALPHA':
                config='/storage/www/cpdnboinc_alpha/ancil_batch_user_config.xml'
                out_path='/storage/www/cpdnboinc_alpha/tmp_batch/'
                batch_prefix='a'

	ParseConfig(config)
	

	plot_batch_run_time(batch,queue,batch_prefix,out_path)
	print "Finished!"

main()
