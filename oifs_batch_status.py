#!/usr/bin/python2.7
#-----------------------------------------------------------------------
# Program: curr_batch_status.py 
# Purpose: To display all returns for current batches.
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
import time
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
	doc	= ET.parse(xmlfilename)
	root_node = doc.getroot()

	BatchDB.host=root_node.findtext('db_host')
	#BatchDB.batchUser=root_node.findtext('batch_user')
	#BatchDB.batchPass=root_node.findtext('batch_passwd')
	BatchDB.batchUser=root_node.findtext('db_user')
        BatchDB.batchPass=root_node.findtext('db_passwd')
        BatchDB.dbexpt=root_node.findtext('db_name')
	BatchDB.dbboinc=root_node.findtext('boinc_db_name')

def query_database(sbatch):
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT bs.batchid as batch, bs.success as success, bs.client_error as error, bs.in_progress as running ,bs.unsent as unsent \
			FROM '+BatchDB.dbexpt+'.cpdn_batch_stats bs'
	condition=" WHERE bs.time>'"+today+"' AND bs.batchid>="+str(sbatch)
	print condition
	order_by=' ORDER BY bs.batchid, bs.time;'

	# Get numbers of results in different states.
	cursor.execute(query_batches+condition+order_by)
	batch_list=cursor.fetchall()
	batch_stats=np.zeros((len(batch_list),5))
	for i in range(0,len(batch_list)):
		batch_stats[i,0]=batch_list[i]['batch']
		batch_stats[i,1]=batch_list[i]['success']
		batch_stats[i,2]=batch_list[i]['error']
		batch_stats[i,3]=batch_list[i]['running']
		batch_stats[i,4]=batch_list[i]['unsent']
	cursor.close()
	return batch_stats

def query_database2(sbatch):
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT w.cpdn_batch as batch,b.name as batch_name, r.outcome as outcome, r.server_state as server_state, count(*) as count \
		FROM '+BatchDB.dbboinc+'.result r JOIN '+BatchDB.dbexpt+'.cpdn_workunit w \
		ON r.workunitid=w.wuid JOIN '+BatchDB.dbexpt+'.cpdn_batch b ON b.id=w.cpdn_batch \
        JOIN 'BatchDB.dbboinc+'.app a ON a.id=b.appid'
	condition=' WHERE r.outcome<=3 and b.ended=0 and a.name like "oifs%" and w.cpdn_batch>='+str(sbatch)
	group_by=' GROUP BY w.cpdn_batch, r.outcome,r.server_state;'

	# Get numbers of results in different states.
	cursor.execute(query_batches+condition+group_by)
	batch_list=cursor.fetchall()
	batch_names={}
	batch_stats_all=[]
	for i in range(0,len(batch_list)):
		batch_stats_all.append(int(batch_list[i]['batch']))
	batch_stats_set=set(batch_stats_all)

	batch_stats=np.zeros((len(batch_stats_set),5))

	for b,batch_no in enumerate(sorted(batch_stats_set)):
		for i in range(0,len(batch_list)):
			if batch_no == batch_list[i]['batch']:
				batch_stats[b,0]=int(batch_list[i]['batch'])
				if batch_stats[b,0]==int(batch_list[i]['batch']) and batch_list[i]['outcome']==1:
					batch_stats[b,1]=batch_list[i]['count']
				if batch_stats[b,0]==int(batch_list[i]['batch']) and batch_list[i]['outcome']==3:
					batch_stats[b,2]=batch_list[i]['count']
				if batch_stats[b,0]==int(batch_list[i]['batch']) and batch_list[i]['outcome']==0 and batch_list[i]['server_state']==4:
					batch_stats[b,3]=batch_list[i]['count']
				if batch_stats[b,0]==int(batch_list[i]['batch']) and batch_list[i]['outcome']==0 and batch_list[i]['server_state']==2:
					batch_stats[b,4]=batch_list[i]['count']
				if batch_stats[b,0]==int(batch_list[i]['batch']):
					batch_names["Batch"+str(int(batch_stats[b,0]))]=batch_list[i]['batch_name']
	cursor.close()
	return batch_stats,batch_names

def get_hard_fails(sbatch):
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT grp.batch as batchno, count(*) as count \
		FROM (SELECT w.cpdn_batch as batch, w.name, count(*) as outcount \
		FROM '+BatchDB.dbexpt+'.cpdn_workunit w JOIN '+BatchDB.dbboinc+'.result r \
		ON r.workunitid=w.wuid WHERE w.cpdn_batch>='+str(sbatch)+' AND r.outcome=3 GROUP BY w.cpdn_batch, w.name) as grp \
		JOIN '+BatchDB.dbexpt+'.cpdn_batch b ON b.id=grp.batch WHERE grp.outcount=IFNULL(b.max_results_per_workunit,3) \
		GROUP BY grp.batch;'
	
	# Get numbers of workunits failed three times.
	cursor.execute(query_batches)
	batch_list=cursor.fetchall()
	batch_hard_fails={}
	for i in range(0,len(batch_list)):
		batch_hard_fails["Batch"+str(int(batch_list[i]['batchno']))]=int(batch_list[i]['count'])
	cursor.close()
	return batch_hard_fails

def get_test_batches(sbatch):
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )
	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT b.id as test_batch FROM '+BatchDB.dbexpt+'.cpdn_batch b \
		JOIN '+BatchDB.dbexpt+'.cpdn_project p ON p.id=b.projectid \
        JOIN 'BatchDB.dbboinc+'.app a ON a.id=b.appid'
	condition=' WHERE p.name="TESTING" and b.id>='+str(sbatch)+' and a.name like  "oifs%";'

	# Get numbers of results in different states.
	cursor.execute(query_batches+condition)
	batch_list=cursor.fetchall()
	test_batches=[]
	for i in range(0,len(batch_list)):
		test_batches.append(int(batch_list[i]['test_batch']))

	cursor.close()
	return test_batches

def get_sbatch():
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )

	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT b.id as last_batch FROM '+BatchDB.dbexpt+'.cpdn_batch b JOIN 'BatchDB.dbboinc+'.app a ON a.id=b.appid WHERE b.ended=0 and a.name like "oifs%"'
	order_by=' ORDER BY b.id DESC LIMIT 51;'
	# Get numbers of results in different states.
	cursor.execute(query_batches+order_by)
	lbatch=cursor.fetchall()
	# Find the starting batch 35 batches less than the last batch
	sbatch=int(lbatch[-1]['last_batch'])
	cursor.close()
	return sbatch

def get_tot_wu():
	tot_workunits={}
	max_results_per_wu={}
	db = MySQLdb.connect(BatchDB.host,BatchDB.batchUser,BatchDB.batchPass,BatchDB.dbexpt )

	cursor = db.cursor(MySQLdb.cursors.DictCursor)

	query_batches = 'SELECT b.id as batchno, b.number_of_workunits as tot_wu, IFNULL(b.max_results_per_workunit,3) as max_wu \
		FROM '+BatchDB.dbexpt+'.cpdn_batch b'
	order_by=' ORDER BY b.id;'
	# Get numbers of results in different states.
	cursor.execute(query_batches+order_by)
	lbatch=cursor.fetchall()
	# Find the starting batch 35 batches less than the last batch
	for i in range(0,len(lbatch)):
		tot_workunits["Batch"+str(int(lbatch[i]['batchno']))]=int(lbatch[i]['tot_wu'])
		max_results_per_wu["Batch"+str(int(lbatch[i]['batchno']))]=int(lbatch[i]['max_wu'])
	cursor.close()
	return tot_workunits,max_results_per_wu

def plot_batch_stats(out_path,batch_prefix):
	# Set the plot font size
	font = {'family' : 'sans-serif','size':14}
	matplotlib.rc('font', **font)
	sbatch=get_sbatch()
	print "Starting from batch"+str(sbatch)
	batch_stats,batch_names=query_database2(sbatch)
	test_batches=get_test_batches(sbatch)
	print test_batches

	tot_workunits,max_results_per_wu=get_tot_wu()
	batch_hard_fails=get_hard_fails(sbatch)

	fig = plt.figure()
	ax=fig.add_subplot(1,1,1)

	N=len(batch_stats[:,0])	
	batch_labels=[]

	first_success=True
	first_failure=True
	first_running=True
	first_unsent=True

	for i in range(0,N):
		ind = np.arange(i,i+1,0.25)  # the x locations for the groups
		width = 0.25       # the width of the bars
		batch_tot=tot_workunits["Batch"+str(int(batch_stats[i,0]))]
		max_results=max_results_per_wu["Batch"+str(int(batch_stats[i,0]))]
		#batch_tot=batch_stats[i,1]+(batch_stats[i,2]*0.3)+batch_stats[i,3]+batch_stats[i,4]
		Success=(batch_stats[i,1]/batch_tot)*100
		Failure=(batch_stats[i,2]/(max_results*batch_tot))*100
		if "Batch"+str(int(batch_stats[i,0])) in batch_hard_fails.keys():
			HardFails=(float(batch_hard_fails["Batch"+str(int(batch_stats[i,0]))])/batch_tot)*100
		else:
			HardFails=0
		Running=(batch_stats[i,3]/batch_tot)*100
		Unsent=(batch_stats[i,4]/batch_tot)*100
		print "Batch"+str(int(batch_stats[i,0]))+" Success "+str(Success)+"% Failure "+str(Failure)+"% Hard Failure "+str(HardFails)+"% Running "+str(Running)+"% Unsent "+str(Unsent)+"%"
		if int(batch_stats[i,0]) in test_batches:
			plt.axvspan(i, i+1, facecolor='Silver', edgecolor='Silver',alpha=0.5,zorder=0)
		
		if Success!=0:
			if first_success==True:
				rects1 = ax.bar(ind[0], Success, width, color='SpringGreen',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,label="Success",zorder=1)
				first_success=False
			else:
				rects1 = ax.bar(ind[0], Success, width, color='SpringGreen',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,zorder=1)
		if Failure!=0:
				if first_failure==True:
					rects2 = ax.bar(ind[1], Failure, width, color='Red',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.4,zorder=1)
					rects2 = ax.bar(ind[1], HardFails, width, color='Red',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.4,label="Failure",zorder=2)
					first_failure=False
				else:
					rects2 = ax.bar(ind[1], Failure, width, color='Red',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.4,zorder=1)
					rects2 = ax.bar(ind[1], HardFails, width, color='Red',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.4,zorder=1)
		if Running!=0:
			if first_running==True:
				rects3 = ax.bar(ind[2], Running, width, color='Orange',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,label="Running",zorder=1)
				first_running=False
			else:
				rects3 = ax.bar(ind[2], Running, width, color='Orange',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,zorder=1)
		if Unsent!=0:
			if first_unsent==True:
				rects4 = ax.bar(ind[3], Unsent, width, color='RoyalBlue',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,label="Unsent",zorder=1)
				first_unsent=False
			else:
				rects4 = ax.bar(ind[3], Unsent, width, color='RoyalBlue',edgecolor='Black',linewidth=0.8,align='edge',alpha=0.5,zorder=1)
		batch_labels.append("Batch "+batch_prefix+str(int(batch_stats[i,0])))
	
	ax.set_title("Batch Statistics "+today_title)
	ax.set_xlim([0,N])
	ax.set_ylim([0,100])
	ind=np.arange(N)
	ax.set_xticks(ind)
	ax.set_xticks(ind+(0.6),minor=True)
	ax.set_xticklabels(" ")
	ax.tick_params(axis='x', which='major',direction='out',bottom='on', top='off')
	ax.tick_params(axis='x', which='minor',bottom='off', top='off')
	ax.set_xticklabels(batch_labels,rotation='vertical',minor=True,fontsize=10)
	ax.set_ylabel("Percentage",fontsize=12)
	plt.setp(ax.get_xticklabels(),fontsize=10)
	plt.setp(ax.get_yticklabels(),fontsize=10)
	plt.legend(loc="upper left",prop={"size": 8},fancybox=True,numpoints=1)
	plt.tight_layout()
	plt.grid(linestyle=':',zorder=0)
	fig.set_size_inches(12.5, 5)
	fig.savefig(out_path+"oifs_batch_statistics.png")

	

def main():
	print time.strftime("%Y/%m/%d %H:%M:%S") + " Starting oifs_batch_stats.py"
	project=sys.argv[1]
	host=gethostname()
	if project=='CPDN' or host=='caerus' or host =='hesperus':
		config='/storage/www/cpdnboinc/ancil_batch_user_config.xml'
		out_path='/storage/www/cpdnboinc/html/user/'
		batch_prefix=''
	elif project=='CPDN_DEV':
		config='/storage/www/cpdnboinc_dev/ancil_batch_user_config.xml'
		out_path='/storage/www/cpdnboinc_dev/html/user/'
		batch_prefix='d'
	elif project=='CPDN_ALPHA':
		config='/storage/www/cpdnboinc_alpha/ancil_batch_user_config.xml'
		out_path='/storage/www/cpdnboinc_alpha/html/user/'
		batch_prefix='a'

	ParseConfig(config) 
	plot_batch_stats(out_path,batch_prefix)
	print "Finished!"

main()
