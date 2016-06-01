<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output media-type="text/xml" method="xml" indent="yes" encoding="utf-8" omit-xml-declaration="no" />

	<xsl:template match="siteMap">
		<siteMap>
			<xsl:apply-templates select="siteMapNode[@parent_id='0']" />
		</siteMap>
	</xsl:template>

	<xsl:template match="siteMapNode">
			<xsl:variable name="page_id" select="@page_id" />
			<siteMapNode>
				<xsl:for-each select="./@*">
					<xsl:attribute name="{name()}"><xsl:value-of select="." /></xsl:attribute>
				</xsl:for-each>
				<xsl:apply-templates select="/siteMap/siteMapNode[@parent_id=$page_id]" />
			</siteMapNode>
	</xsl:template>

</xsl:stylesheet>